<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Exception;
use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\CustomPage;
use App\Models\File;
use App\Models\InterfaceRichText;
use App\Models\Newsletter;
use App\Models\Page;
use App\Models\Requirement;
use App\Render;
use App\Services\ConfigService;
use App\Services\DbService;
use App\Services\FileService;
use App\Services\ImageService;
use App\Services\OrmService;
use App\Services\RenderService;
use App\Services\UploadHandler;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExplorerController extends AbstractAdminController
{
    private FileService $fileService;

    public function __construct()
    {
        $this->fileService = new FileService();
    }

    /**
     * Show the file manager.
     */
    public function index(Request $request): Response
    {
        $currentDir = strval($request->cookies->get('admin_dir', '/images'));

        $data = [
            'returnType' => $request->get('return', ''),
            'returnid'   => $request->get('returnid', ''),
            'bgcolor'    => ConfigService::getString('bgcolor'),
            'dirs'       => $this->fileService->getRootDirs($currentDir),
        ];

        return $this->render('admin/explorer', $data);
    }

    /**
     * Render subfolder.
     */
    public function folders(Request $request): JsonResponse
    {
        $path = $request->getRequestString('path') ?? '';
        $move = $request->query->getBoolean('move');
        $currentDir = strval($request->cookies->get('admin_dir', '/images'));

        $html = app(RenderService::class)->render(
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
     * @todo only output json, let fronend generate html and init objects
     */
    public function files(Request $request): JsonResponse
    {
        $path = $request->getRequestString('path') ?? '';
        $returnType = $request->getRequestString('return') ?? '';

        $this->fileService->checkPermittedPath($path);

        $app = app();

        $files = scandir($app->basePath($path)) ?: [];
        natcasesort($files);

        $html = '';
        $fileData = [];
        foreach ($files as $fileName) {
            if ('.' === mb_substr($fileName, 0, 1) || is_dir($app->basePath($path . '/' . $fileName))) {
                continue;
            }

            $filePath = $path . '/' . $fileName;
            $file = File::getByPath($filePath);
            if (!$file) {
                $file = File::fromPath($filePath)->save();
            }

            $html .= $this->fileService->filehtml($file, $returnType);
            $fileData[] = $this->fileService->fileAsArray($file);
        }

        return new JsonResponse(['id' => 'files', 'html' => $html, 'files' => $fileData]);
    }

    /**
     * Search for files.
     */
    public function search(Request $request): JsonResponse
    {
        $db = app(DbService::class);

        $returnType = $request->getRequestString('return') ?? '';
        $qpath = $db->escapeWildcards($request->getRequestString('qpath') ?? '');
        $qalt = $db->escapeWildcards($request->getRequestString('qalt') ?? '');

        $qtype = $request->getRequestString('qtype');
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
                $sql .= 'MATCH(path) AGAINST(' . $db->quote($qpath) . ')>0';
            }
            if ($qpath && $qalt) {
                $sql .= ' OR ';
            }
            if ($qalt) {
                $sql .= 'MATCH(alt) AGAINST(' . $db->quote($qalt) . ')>0';
            }
            if ($qpath) {
                $sql .= ' OR `path` LIKE ' . $db->quote('%' . $qpath . '%');
            }
            if ($qalt) {
                $sql .= ' OR `alt` LIKE ' . $db->quote('%' . $qalt . '%');
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
                $sqlSelect .= 'MATCH(path) AGAINST(' . $db->quote($qpath) . ')';
            }
            if ($qpath && $qalt) {
                $sqlSelect .= ' + ';
            }
            if ($qalt) {
                $sqlSelect .= 'MATCH(alt) AGAINST(' . $db->quote($qalt) . ')';
            }
            if ($qpath && $qalt) {
                $sqlSelect .= ')';
            }
            $sqlSelect .= ' AS score';
            $sql = $sqlSelect . $sql;
            $sql .= ' ORDER BY `score` DESC';
        }

        $html = '';
        $fileData = [];

        $files = app(OrmService::class)->getByQuery(File::class, 'SELECT *' . $sql);
        foreach ($files as $file) {
            if ('unused' !== $qtype || !$file->isInUse()) {
                $html .= $this->fileService->filehtml($file, $returnType);
                $fileData[] = $this->fileService->fileAsArray($file);
            }
        }

        return new JsonResponse(['id' => 'files', 'html' => $html, 'files' => $fileData]);
    }

    public function fileDelete(Request $request, int $id): JsonResponse
    {
        $file = app(OrmService::class)->getOne(File::class, $id);
        if ($file) {
            if ($file->isInUse()) {
                throw new InvalidInput(_('The file can not be deleted because it is in use.'), Response::HTTP_LOCKED);
            }

            $file->delete();
        }

        return new JsonResponse(['id' => $id]);
    }

    /**
     * Create new folder.
     */
    public function folderCreate(Request $request): JsonResponse
    {
        $path = $request->getRequestString('path') ?? '';
        $name = $this->fileService->cleanFileName($request->getRequestString('name') ?? '');
        $newPath = $path . '/' . $name;

        $this->fileService->createFolder($newPath);

        return new JsonResponse([]);
    }

    /**
     * Endpoint for deleting a folder.
     */
    public function folderDelete(Request $request): JsonResponse
    {
        $path = $request->getRequestString('path') ?? '';
        $this->fileService->deleteFolder($path);

        return new JsonResponse([]);
    }

    public function fileView(Request $request, int $id): Response
    {
        $file = app(OrmService::class)->getOne(File::class, $id);
        if (!$file) {
            throw new InvalidInput(_('File not found.'), Response::HTTP_NOT_FOUND);
        }

        $template = 'admin/popup-image';
        if (0 === mb_strpos($file->getMime(), 'video/')) {
            $template = 'admin/popup-video';
        } elseif (0 === mb_strpos($file->getMime(), 'audio/')) {
            $template = 'admin/popup-audio';
        }

        return $this->render($template, ['file' => $file]);
    }

    /**
     * Check if a file already exists.
     */
    public function fileExists(Request $request): JsonResponse
    {
        $path = $request->getRequestString('path') ?? '';
        $type = $request->getRequestString('type') ?? '';

        $pathinfo = pathinfo($path);

        if ('image' === $type) {
            $pathinfo['extension'] = 'jpg';
        } elseif ('lineimage' === $type) {
            $pathinfo['extension'] = 'png';
        } elseif (empty($pathinfo['extension'])) {
            $pathinfo['extension'] = 'jpg';
        }

        $path = ($pathinfo['dirname'] ?? '') . '/' . $this->fileService->cleanFileName($pathinfo['filename']);
        $fullPath = app()->basePath($path);
        $fullPath .= '.' . $pathinfo['extension'];

        return new JsonResponse(['exists' => is_file($fullPath), 'name' => basename($fullPath)]);
    }

    /**
     * Update image description.
     *
     * @todo make db fixer check for missing alt="" in <img>
     */
    public function fileDescription(Request $request, int $id): JsonResponse
    {
        $orm = app(OrmService::class);

        $file = $orm->getOne(File::class, $id);
        if (!$file) {
            throw new InvalidInput(_('File not found.'), Response::HTTP_NOT_FOUND);
        }

        $description = $request->getRequestString('description') ?? '';
        $file->setDescription($description)->save();

        $db = app(DbService::class);

        foreach ([Page::class, CustomPage::class, Requirement::class, Newsletter::class] as $className) {
            $richTexts = $orm->getByQuery(
                $className,
                'SELECT * FROM `' . $className::TABLE_NAME
                    . '` WHERE `text` LIKE ' . $db->quote('%="' . $file->getPath() . '"%')
            );
            $this->updateAltInHtml($richTexts, $file);
        }

        return new JsonResponse(['id' => $id, 'description' => $description]);
    }

    /**
     * Update alt text for images in HTML text.
     *
     * @param InterfaceRichText[] $richTexts
     *
     * @throws Exception
     */
    private function updateAltInHtml(array $richTexts, File $file): void
    {
        foreach ($richTexts as $richText) {
            $html = $richText->getHtml();
            $html = preg_replace(
                [
                    '/(<img[^>]+src="' . preg_quote($file->getPath(), '/') . '"[^>]+alt=")[^"]*("[^>]*>)/iu',
                    '/(<img[^>]+alt=")[^"]*("[^>]+src="' . preg_quote($file->getPath(), '/') . '"[^>]*>)/iu',
                ],
                '\1' . htmlspecialchars($file->getDescription(), ENT_COMPAT | ENT_XHTML) . '\2',
                $html
            );
            if (null === $html) {
                throw new Exception('preg_replace failed');
            }
            $richText->setHtml($html)->save();
        }
    }

    /**
     * File viwer.
     */
    public function fileMoveDialog(Request $request, int $id): Response
    {
        $currentDir = strval($request->cookies->get('admin_dir', '/images'));

        $file = app(OrmService::class)->getOne(File::class, $id);
        if (!$file) {
            throw new InvalidInput(_('File not found.'), Response::HTTP_NOT_FOUND);
        }

        $data = [
            'file' => $file,
            'dirs' => $this->fileService->getRootDirs($currentDir),
        ];

        return $this->render('admin/file-move', $data);
    }

    /**
     * Upload dialog.
     */
    public function fileUploadDialog(Request $request): Response
    {
        $maxbyte = min(
            $this->fileService->returnBytes(ini_get('post_max_size') ?: '0'),
            $this->fileService->returnBytes(ini_get('upload_max_filesize') ?: '0')
        );

        $data = [
            'maxbyte'   => $maxbyte,
            'activeDir' => $request->get('path'),
        ];

        return $this->render('admin/file-upload', $data);
    }

    public function fileUpload(Request $request): Response
    {
        /** @var ?UploadedFile */
        $uploadedFile = $request->files->get('upload');
        if (!$uploadedFile) {
            throw new InvalidInput(_('No file received.'));
        }

        $currentDir = strval($request->cookies->get('admin_dir', '/images'));
        $targetDir = $request->getRequestString('dir') ?? $currentDir;
        $destinationType = $request->getRequestString('type') ?? '';
        $description = $request->getRequestString('alt') ?? '';

        $uploadHandler = new UploadHandler($targetDir);
        $file = $uploadHandler->process($uploadedFile, $destinationType, $description);

        $data = [
            'uploaded' => 1,
            'fileName' => basename($file->getPath()),
            'url'      => $file->getPath(),
            'width'    => $file->getWidth(),
            'height'   => $file->getHeight(),
        ];

        return new JsonResponse($data);
    }

    /**
     * Rename or relocate file.
     *
     * @throws Exception
     */
    public function fileRename(Request $request, int $id): JsonResponse
    {
        try {
            $file = app(OrmService::class)->getOne(File::class, $id);
            if (!$file) {
                throw new InvalidInput(_('File not found.'), Response::HTTP_NOT_FOUND);
            }

            $pathinfo = pathinfo($file->getPath());

            $dir = $request->getRequestString('dir') ?? ($pathinfo['dirname'] ?? '');
            $filename = $request->getRequestString('name') ?? $pathinfo['filename'];
            $filename = $this->fileService->cleanFileName($filename);
            $overwrite = $request->request->getBoolean('overwrite');

            $ext = $pathinfo['extension'] ?? '';
            $ext = $ext ? '.' . $ext : '';
            $newPath = $dir . '/' . $filename . $ext;

            if ($file->getPath() === $newPath) {
                return new JsonResponse(['id' => $id, 'filename' => $filename, 'path' => $file->getPath()]);
            }

            if (!$filename) {
                throw new InvalidInput(_('The name is invalid.'));
            }

            $this->fileService->checkPermittedTargetPath($newPath);

            $existingFile = File::getByPath($newPath);
            if ($existingFile) {
                if ($existingFile->isInUse()) {
                    throw new InvalidInput(_('File already exists.'));
                }

                if (!$overwrite) {
                    return new JsonResponse([
                        'yesno' => _(
                            'A file with the same name already exists. Would you like to replace the existing file?'
                        ),
                        'id'    => $id,
                    ]);
                }

                $existingFile->delete();
            }

            if (!$file->move($newPath)) {
                throw new Exception(_('An error occurred with the file operations.'));
            }
        } catch (InvalidInput $exception) {
            return new JsonResponse(
                ['error' => ['message' => $exception->getMessage()], 'id' => $id],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['id' => $id, 'filename' => $filename, 'path' => $newPath]);
    }

    /**
     * Rename directory.
     *
     * @throws Exception
     */
    public function folderRename(Request $request): JsonResponse
    {
        $path = $request->getRequestString('path') ?? '';
        $name = $request->getRequestString('name') ?? '';
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

            $app = app();

            if (file_exists($app->basePath($newPath))) {
                if (!$overwrite) {
                    return new JsonResponse([
                        'yesno' => _(
                            'A file with the same name already exists. Would you like to replace the existing file?'
                        ),
                        'path'  => $path,
                    ]);
                }

                $this->fileService->deleteFolder($newPath);
            }

            if (!rename($app->basePath($path), $app->basePath($newPath))) {
                throw new Exception(_('An error occurred with the file operations.'));
            }
        } catch (InvalidInput $exception) {
            return new JsonResponse(
                ['error' => ['message' => $exception->getMessage()], 'path' => $path],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->fileService->replaceFolderPaths($path, $newPath);

        return new JsonResponse(['filename' => $name, 'path' => $path, 'newPath' => $newPath]);
    }

    /**
     * Image editing window.
     */
    public function imageEditWidget(Request $request, int $id): Response
    {
        $file = app(OrmService::class)->getOne(File::class, $id);
        if (!$file) {
            throw new InvalidInput(_('File not found.'), Response::HTTP_NOT_FOUND);
        }

        $mode = $request->get('mode');

        $fileName = '';
        if ('thb' === $mode) {
            $fileName = pathinfo($file->getPath(), PATHINFO_FILENAME) . '-thb';
        }

        $data = [
            'textWidth'   => ConfigService::getInt('text_width'),
            'thumbWidth'  => ConfigService::getInt('thumb_width'),
            'thumbHeight' => ConfigService::getInt('thumb_height'),
            'mode'        => $mode,
            'fileName'    => $fileName,
            'file'        => $file,
        ];

        return $this->render('admin/image-edit', $data);
    }

    /**
     * Dynamic image.
     *
     * @throws Exception
     */
    public function image(Request $request, int $id): Response
    {
        $file = app(OrmService::class)->getOne(File::class, $id);
        if (!$file) {
            throw new InvalidInput(_('File not found.'), Response::HTTP_NOT_FOUND);
        }

        $path = $file->getPath();

        $noCache = $request->query->getBoolean('noCache');

        $app = app();

        $timestamp = filemtime($app->basePath($path));
        if (false === $timestamp) {
            throw new Exception('File not found.', Response::HTTP_NOT_FOUND);
        }

        if (!$noCache) {
            $response = $this->cachedResponse(null, $timestamp, 2592000);
            if ($response->isNotModified($request)) {
                return $response;
            }
        }

        $image = $this->createImageServiceFomRequest($request->query, $app->basePath($path));
        if ($image->isNoOp()) {
            return redirect($path, Response::HTTP_MOVED_PERMANENTLY);
        }

        $targetPath = tempnam(sys_get_temp_dir(), 'image');
        if (!$targetPath) {
            throw new Exception('Failed to create temporary file');
        }

        $type = 'jpeg';
        if ('image/jpeg' !== $file->getMime()) {
            $type = 'png';
        }

        $image->processImage($targetPath, $type);

        $response = new BinaryFileResponse($targetPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, pathinfo($path, PATHINFO_BASENAME));

        return $this->cachedResponse($response, $timestamp, 2592000);
    }

    /**
     * Process an image.
     */
    public function imageSave(Request $request, int $id): Response
    {
        $file = app(OrmService::class)->getOne(File::class, $id);
        if (!$file) {
            throw new InvalidInput(_('File not found.'), Response::HTTP_NOT_FOUND);
        }

        $path = $file->getPath();
        $fullPath = app()->basePath($path);

        $image = $this->createImageServiceFomRequest($request->request, $fullPath);
        if ($image->isNoOp()) {
            return $this->createImageResponse($file);
        }
        if ($file->isInUse(true)) {
            throw new InvalidInput(_('Image can not be changed as it used in a text.'), Response::HTTP_LOCKED);
        }

        $type = 'jpeg';
        $mime = 'image/jpeg';
        if ('image/jpeg' !== $file->getMime()) {
            $type = 'png';
            $mime = 'image/png';
        }

        $image->processImage($fullPath, $type);

        $file->setWidth($image->getWidth())
            ->setHeight($image->getHeight())
            ->setMime($mime)
            ->setSize(filesize($fullPath) ?: 0)
            ->save();

        return $this->createImageResponse($file);
    }

    /**
     * Generate a thumbnail image from an existing image.
     */
    public function imageSaveThumb(Request $request, int $id): Response
    {
        $file = app(OrmService::class)->getOne(File::class, $id);
        if (!$file) {
            throw new InvalidInput(_('File not found.'), Response::HTTP_NOT_FOUND);
        }

        $path = $file->getPath();

        $app = app();

        $image = $this->createImageServiceFomRequest($request->request, $app->basePath($path));
        if ($image->isNoOp()) {
            return $this->createImageResponse($file);
        }

        $type = 'jpeg';
        $ext = 'jpg';
        $mime = 'image/jpeg';
        if ('image/jpeg' !== $file->getMime()) {
            $type = 'png';
            $ext = 'png';
            $mime = 'image/png';
        }

        $pathInfo = pathinfo($path);
        $newPath = ($pathInfo['dirname'] ?? '') . '/' . $pathInfo['filename'] . '-thb.' . $ext;

        if (File::getByPath($newPath)) {
            throw new InvalidInput(_('Thumbnail already exists.'));
        }
        $image->processImage($app->basePath($newPath), $type);

        $newFile = File::fromPath($newPath);
        $newFile->setDescription($file->getDescription())->save();

        return $this->createImageResponse($newFile);
    }

    /**
     * Create an image service from a path and the request parameteres.
     *
     * @param ParameterBag<string, int> $parameterBag
     */
    private function createImageServiceFomRequest(ParameterBag $parameterBag, string $path): ImageService
    {
        $image = new ImageService($path);
        $image->setCrop(
            $parameterBag->getInt('cropX'),
            $parameterBag->getInt('cropY'),
            $parameterBag->getInt('cropW'),
            $parameterBag->getInt('cropH')
        );
        $image->setScale($parameterBag->getInt('maxW'), $parameterBag->getInt('maxH'));
        $image->setFlip($parameterBag->getInt('flip'));
        $image->setRotate($parameterBag->getInt('rotate'));

        return $image;
    }

    /**
     * Create an image response for the image editor.
     */
    private function createImageResponse(File $file): JsonResponse
    {
        return new JsonResponse([
            'id'     => $file->getId(),
            'path'   => $file->getPath(),
            'width'  => $file->getWidth(),
            'height' => $file->getHeight(),
        ]);
    }
}
