<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\File;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Service\FileService;
use AGCMS\Service\UploadHandler;
use AJenbo\Image;
use DateTime;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

class ExplorerController extends AbstractAdminController
{
    /** @var FileService */
    private $fileService;

    public function __construct()
    {
        $this->fileService = new FileService();
    }

    /**
     * Show the file manager.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $currentDir = $request->cookies->get('admin_dir', '/images');

        $data = [
            'returnType' => $request->get('return', ''),
            'returnid'   => $request->get('returnid', ''),
            'bgcolor'    => Config::get('bgcolor'),
            'dirs'       => $this->fileService->getRootDirs($currentDir),
        ];

        $content = Render::render('admin/explorer', $data);

        return new Response($content);
    }

    /**
     * Render subfolder.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function folders(Request $request): JsonResponse
    {
        $path = $request->get('path');
        $move = $request->query->getBoolean('move');
        $currentDir = $request->cookies->get('admin_dir', '/images');

        $html = Render::render(
            'admin/partial-listDirs',
            [
                'dirs' => $this->fileService->getSubDirs($path, $currentDir),
                'move' => $move,
            ]
        );

        return new JsonResponse(['id' => $path, 'html' => $html]);
    }

    /**
     * Display a list of files in the selected folder.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function files(Request $request): JsonResponse
    {
        $path = $request->get('path');
        $returnType = $request->get('return', '');

        try {
            $this->fileService->checkPermittedPath($path);
        } catch (InvalidInput $exception) {
            return new JsonResponse(['error' => $exception->getMessage()]);
        }

        $files = scandir(_ROOT_ . $path);
        natcasesort($files);

        $html = '';
        $javascript = '';
        foreach ($files as $fileName) {
            if ('.' === mb_substr($fileName, 0, 1) || is_dir(_ROOT_ . $path . '/' . $fileName)) {
                continue;
            }

            $filePath = $path . '/' . $fileName;
            $file = File::getByPath($filePath);
            if (!$file) {
                $file = File::fromPath($filePath)->save();
            }

            $html .= $this->fileService->filehtml($file, $returnType);
            //TODO reduce net to javascript
            $javascript .= $this->fileService->filejavascript($file);
        }

        return new JsonResponse(['id' => 'files', 'html' => $html, 'javascript' => $javascript]);
    }

    /**
     * Search for files.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $returnType = $request->get('return', '');
        $qpath = db()->escapeWildcards(db()->esc($request->get('qpath', '')));
        $qalt = db()->escapeWildcards(db()->esc($request->get('qalt', '')));

        $qtype = $request->get('qtype');
        $sqlMime = '';
        switch ($qtype) {
            case 'image':
                $sqlMime = "mime IN('image/jpeg', 'image/png', 'image/gif')";
                break;
            case 'imagefile':
                $sqlMime = "mime LIKE 'image/%' AND mime NOT IN('image/jpeg', 'image/png', 'image/gif')";
                break;
            case 'video':
                $sqlMime = "mime LIKE 'video/%'";
                break;
            case 'audio':
                $sqlMime = "mime LIKE 'audio/%'";
                break;
            case 'text':
                $sqlMime = "(
                    mime IN(
                        'application/pdf',
                        'application/msword',
                        'application/vnd.ms-%',
                        'application/vnd.openxmlformats-officedocument.%',
                        'application/vnd.oasis.opendocument.%'
                )";
                break;
            case 'compressed':
                $sqlMime = "mime = 'application/zip'";
                break;
        }

        //Generate search query
        $sql = ' FROM `files`';
        if ($qpath || $qalt || $sqlMime) {
            $sql .= ' WHERE ';
            if ($qpath || $qalt) {
                $sql .= '(';
            }
            if ($qpath) {
                $sql .= "MATCH(path) AGAINST('" . $qpath . "')>0";
            }
            if ($qpath && $qalt) {
                $sql .= ' OR ';
            }
            if ($qalt) {
                $sql .= "MATCH(alt) AGAINST('" . $qalt . "')>0";
            }
            if ($qpath) {
                $sql .= " OR `path` LIKE '%" . $qpath . "%' ";
            }
            if ($qalt) {
                $sql .= " OR `alt` LIKE '%" . $qalt . "%'";
            }
            if ($qpath || $qalt) {
                $sql .= ')';
            }
            if (($qpath || $qalt) && !empty($sqlMime)) {
                $sql .= ' AND ';
            }
            if (!empty($sqlMime)) {
                $sql .= $sqlMime;
            }
        }

        $sqlSelect = '';
        if ($qpath || $qalt) {
            $sqlSelect .= ', ';
            if ($qpath && $qalt) {
                $sqlSelect .= '(';
            }
            if ($qpath) {
                $sqlSelect .= 'MATCH(path) AGAINST(\'' . $qpath . '\')';
            }
            if ($qpath && $qalt) {
                $sqlSelect .= ' + ';
            }
            if ($qalt) {
                $sqlSelect .= 'MATCH(alt) AGAINST(\'' . $qalt . '\')';
            }
            if ($qpath && $qalt) {
                $sqlSelect .= ')';
            }
            $sqlSelect .= ' AS score';
            $sql = $sqlSelect . $sql;
            $sql .= ' ORDER BY `score` DESC';
        }

        $html = '';
        $javascript = '';
        foreach (ORM::getByQuery(File::class, 'SELECT *' . $sql) as $file) {
            assert($file instanceof File);
            if ('unused' !== $qtype || !$file->isinuse()) {
                $html .= $this->fileService->filehtml($file, $returnType);
                $javascript .= $this->fileService->filejavascript($file);
            }
        }

        return new JsonResponse(['id' => 'files', 'html' => $html, 'javascript' => $javascript]);
    }

    /**
     * Delete file.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function fileDelete(Request $request, int $id): JsonResponse
    {
        $file = ORM::getOne(File::class, $id);
        if (!$file) {
            return new JsonResponse(['id' => $id]);
        }

        try {
            if ($file->isInUse()) {
                throw new InvalidInput(_('The file can not be deleted because it is in use.'));
            }

            $file->delete();
        } catch (InvalidInput $exception) {
            return new JsonResponse(['error' => $exception->getMessage()]);
        }

        return new JsonResponse(['id' => $id]);
    }

    /**
     * Create new folder.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function folderCreate(Request $request): JsonResponse
    {
        $path = $request->get('path', '');
        $name = $this->fileService->cleanFileName($request->get('name', ''));
        $newPath = $path . '/' . $name;

        try {
            $this->fileService->createFolder($newPath);
        } catch (InvalidInput $exception) {
            return new JsonResponse(['error' => $exception->getMessage()]);
        }

        return new JsonResponse(['error' => false]);
    }

    /**
     * Endpoint for deleting a folder.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function folderDelete(Request $request): JsonResponse
    {
        $path = $request->get('path', '');
        try {
            $this->fileService->deleteFolder($path);
        } catch (InvalidInput $exception) {
            return new JsonResponse(['error' => $exception->getMessage()]);
        }

        return new JsonResponse(['error' => false]);
    }

    /**
     * File viwer.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function fileView(Request $request, int $id): Response
    {
        /** @var File */
        $file = ORM::getOne(File::class, $id);
        $template = 'admin/popup-image';

        if (0 === mb_strpos($file->getMime(), 'video/')) {
            $template = 'admin/popup-video';
        } elseif (0 === mb_strpos($file->getMime(), 'audio/')) {
            $template = 'admin/popup-audio';
        }

        $content = Render::render($template, ['file' => $file]);

        return new Response($content);
    }

    /**
     * Check if a file already exists.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fileExists(Request $request): JsonResponse
    {
        $path = $request->get('path', '');
        $type = $request->get('type', '');

        $pathinfo = pathinfo($path);

        if ('image' === $type) {
            $pathinfo['extension'] = 'jpg';
        } elseif ('lineimage' == $type) {
            $pathinfo['extension'] = 'png';
        }
        $filePath = _ROOT_ . $pathinfo['dirname'] . '/' . $this->fileService->cleanFileName($pathinfo['filename']);
        if ($pathinfo['extension']) {
            $filePath .= '.' . $pathinfo['extension'];
        }

        return new JsonResponse(['exists' => (bool) is_file($filePath), 'name' => basename($filePath)]);
    }

    /**
     * File viwer.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function fileMoveDialog(Request $request, int $id): Response
    {
        $currentDir = $request->cookies->get('admin_dir', '/images');

        /** @var File */
        $file = ORM::getOne(File::class, $id);

        $data = [
            'file' => $file,
            'dirs' => $this->fileService->getRootDirs($currentDir),
        ];
        $content = Render::render('admin/file-move', $data);

        return new Response($content);
    }

    /**
     * Upload dialog.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function fileUploadDialog(Request $request): Response
    {
        $maxbyte = min(
            $this->fileService->returnBytes(ini_get('post_max_size')),
            $this->fileService->returnBytes(ini_get('upload_max_filesize'))
        );

        $data = [
            'maxbyte'   => $maxbyte,
            'activeDir' => $request->get('path'),
        ];
        $content = Render::render('admin/file-upload', $data);

        return new Response($content);
    }

    /**
     * Upload file.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function fileUpload(Request $request): Response
    {
        /** @var UploadedFile */
        $uploadedFile = $request->files->get('upload');
        $currentDir = $request->cookies->get('admin_dir', '/images');
        $targetDir = $request->get('dir', $currentDir);
        $destinationType = $request->get('type', '');
        $description = $request->get('alt', '');

        try {
            $uploadHandler = new UploadHandler($targetDir);
            $file = $uploadHandler->process($uploadedFile, $destinationType, $description);

            $data = [
                'uploaded' => 1,
                'fileName' => basename($file->getPath()),
                'url'      => $file->getPath(),
                'width'    => $file->getWidth(),
                'height'   => $file->getHeight(),
            ];
        } catch (Throwable $exception) {
            // TODO log errors with sentry
            $data = [
                'uploaded' => 0,
                'error'    => [
                    'message' => _('Error: ') . $exception->getMessage(),
                ],
            ];
        }

        return new JsonResponse($data);
    }

    //TODO if force, refresh folder or we might have duplicates displaying in the folder.

    /**
     * Rename or relocate file.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function fileRename(Request $request, int $id): JsonResponse
    {
        /** @var File */
        $file = ORM::getOne(File::class, $id);
        $pathinfo = pathinfo($file->getPath());

        $dir = $request->request->get('dir', $pathinfo['dirname']);
        $filename = $request->request->get('name', $pathinfo['filename']);
        $filename = $this->fileService->cleanFileName($filename);
        $overwrite = $request->request->getBoolean('overwrite');

        $newPath = $dir . '/' . $filename . '.' . $pathinfo['extension'];

        if ($file->getPath() === $newPath) {
            return new JsonResponse(['id' => $id, 'filename' => $filename, 'path' => $file->getPath()]);
        }

        try {
            if (!$filename) {
                throw new InvalidInput(_('The name is invalid.'));
            }

            $this->fileService->checkPermittedTargetPath($newPath);

            $existingFile = File::getByPath($newPath);
            if ($existingFile) {
                if ($existingFile->isInUse()) {
                    throw new InvalidInput(_('An in use file already has that name.'));
                }

                if (!$overwrite) {
                    return new JsonResponse([
                        'yesno' => _('A file with the same name already exists. Would you like to replace the existing file?'),
                        'id' => $id,
                    ]);
                }

                $existingFile->delete();
            }

            if (!$file->move($newPath)) {
                throw new InvalidInput(_('An error occurred with the file operations.'));
            }
        } catch (InvalidInput $exception) {
            return new JsonResponse(['error' => $exception->getMessage(), 'id' => $id]);
        }

        return new JsonResponse(['id' => $id, 'filename' => $filename, 'path' => $newPath]);
    }

    /**
     * Rename directory.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function folderRename(Request $request): JsonResponse
    {
        $path = $request->request->get('path', '');
        $name = $request->request->get('name', '');
        $name = $this->fileService->cleanFileName($name);
        $overwrite = $request->request->getBoolean('overwrite');

        $dirname = pathinfo($path, PATHINFO_DIRNAME);
        $newPath = $dirname . '/' . $name;

        if ($path === $newPath) {
            return new JsonResponse(['filename' => $name, 'path' => $path, 'newPath' => $newPath]);
        }

        try {
            if (!$name) {
                throw new InvalidInput(_('The name is invalid.'));
            }

            $this->fileService->checkPermittedTargetPath($path);

            if (file_exists(_ROOT_ . $newPath)) {
                if (!$overwrite) {
                    return new JsonResponse([
                        'yesno' => _('A file with the same name already exists. Would you like to replace the existing file?'),
                        'path'  => $path,
                    ]);
                }

                $this->fileService->deleteFolder($newPath);
            }

            if (!rename(_ROOT_ . $path, _ROOT_ . $newPath)) {
                throw new InvalidInput(_('An error occurred with the file operations.'));
            }
        } catch (InvalidInput $exception) {
            return new JsonResponse(['error' => $exception->getMessage(), 'path' => $path]);
        }

        $this->fileService->replaceFolderPaths($path, $newPath);

        return new JsonResponse(['filename' => $name, 'path' => $path, 'newPath' => $newPath]);
    }

    /**
     * Image editing window
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function imageEditWidget(Request $request, int $id): Response
    {
        $file = ORM::getOne(File::class, $id);
        $mode = $request->get('mode');

        $fileName = '';
        if ('thb' === $mode) {
            $fileName = pathinfo($file->getPath(), PATHINFO_FILENAME) . '-thb';
        }

        $data = [
            'textWidth' => Config::get('text_width'),
            'thumbWidth' => Config::get('thumb_width'),
            'thumbHeight' => Config::get('thumb_height'),
            'mode' => $mode,
            'fileName' => $fileName,
            'file' => $file,
        ];
        $content = Render::render('admin/image-edit', $data);

        return new Response($content);
    }

    /**
     * Dynamic image.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function image(Request $request, int $id): Response
    {
        $file = ORM::getOne(File::class, $id);
        $path = $file->getPath();

        $timestamp = filemtime(_ROOT_ . $path);
        $lastModified = DateTime::createFromFormat('U', (string) $timestamp);

        $response = new Response();
        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            $response->setMaxAge(2592000); // one month
            return $response; // 304
        }

        $cropW = $request->get('cropW');
        $cropW = min($file->getWidth(), $cropW) ?: $file->getWidth();
        $cropH = $request->get('cropH');
        $cropH = min($file->getHeight(), $cropH) ?: $file->getHeight();
        $cropX = $request->query->getInt('cropX');
        $cropY = $request->query->getInt('cropY');
        $cropX = $cropX + $cropW < $file->getWidth() ? $cropX : 0;
        $cropY = $cropY + $cropH < $file->getHeight() ? $cropY : 0;
        $maxW = $request->get('maxW', $file->getWidth());
        $maxH = $request->get('maxH', $file->getHeight());
        $flip = $request->query->getInt('flip');
        $rotate = $request->query->getInt('rotate', 0);

        $type = 'jpeg';
        $guesser = MimeTypeGuesser::getInstance();
        $mime = $guesser->guess(_ROOT_ . $path);
        if ('image/jpeg' !== $mime) {
            $type = 'png';
        }

        $image = new Image(_ROOT_ . $path);

        // Crop image
        $image->crop($cropX, $cropY, $cropW, $cropH);

        // Trim image whitespace
        $imageContent = $image->findContent();

        $maxW = min($maxW, $imageContent['width']);
        $maxH = min($maxH, $imageContent['height']);

        if (!$flip && !$rotate && $maxW === $file->getWidth() && $maxH === $file->getHeight()) {
            return $this->redirect($request, $path, Response::HTTP_MOVED_PERMANENTLY);
        }

        $image->crop(
            $imageContent['x'],
            $imageContent['y'],
            $imageContent['width'],
            $imageContent['height']
        );

        // Resize
        $image->resize($maxW, $maxH);

        // Flip / mirror
        if ($flip) {
            $image->flip(1 === $flip ? 'x' : 'y');
        }

        $image->rotate($rotate);

        $target = tempnam(sys_get_temp_dir(), 'image');

        $image->save($target, $type);

        $response = new BinaryFileResponse($target);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, pathinfo($path, PATHINFO_BASENAME));
        $response->setMaxAge(2592000); // one month
        $response->setLastModified($lastModified);

        return $response;
    }
}
