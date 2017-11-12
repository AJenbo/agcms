<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\File;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExplorerController extends AbstractAdminController
{
    /**
     * Show the file manager
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $data = [
            'returnType' => $request->get('return', ''),
            'returnid' => $request->get('returnid', ''),
            'bgcolor'  => Config::get('bgcolor'),
            'dirs'     => $this->getRootDirs(),
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

        $html = Render::render(
            'admin/partial-listDirs',
            [
                'dirs' => $this->getSubDirs($path),
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
            $this->checkPermittedPath($path);
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

            $html .= $this->filehtml($file, $returnType);
            //TODO reduce net to javascript
            $javascript .= $this->filejavascript($file);
        }

        return new JsonResponse(['id' => 'files', 'html' => $html, 'javascript' => $javascript]);
    }

    /**
     * Search for files
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
                $html .= $this->filehtml($file, $returnType);
                $javascript .= $this->filejavascript($file);
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
     * Create new folder
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function folderCreate(Request $request): JsonResponse
    {
        $path = $request->get('path', '');
        $name = $this->cleanFileName($request->get('name', ''));
        $newPath = $path . '/' . $name;

        try {
            $this->checkPathIsAvalible($newPath);

            if (!@mkdir(_ROOT_ . $path . '/' . $name, 0771)) {
                throw new InvalidInput(
                    _('Could not create folder, you may not have sufficient rights to this folder.')
                );
            }
        } catch (InvalidInput $exception) {
            return new JsonResponse(['error' => $exception->getMessage()]);
        }

        return new JsonResponse(['error' => false]);
    }

    /**
     * Endpoint for deleting a folder
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function folderDelete(Request $request): JsonResponse
    {
        $path = $request->get('path', '');
        try {
            $this->checkPermittedPath($path);

            $this->deleteFolder($path);
        } catch (InvalidInput $exception) {
            return new JsonResponse(['error' => $exception->getMessage()]);
        }

        return new JsonResponse(['error' => false]);
    }

    /**
     * File viwer
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

        if (mb_strpos($file->getMime(), 'video/') === 0) {
            $template = 'admin/popup-video';
        } elseif (mb_strpos($file->getMime(), 'audio/') === 0) {
            $template = 'admin/popup-audio';
        }

        $content = Render::render($template, ['file' => $file]);

        return new Response($content);
    }

    /**
     * File viwer
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function fileMoveDialog(Request $request, int $id): Response
    {
        /** @var File */
        $file = ORM::getOne(File::class, $id);
        $template = 'admin/popup-image';

        $data = [
            'file' => $file,
            'dirs' => $this->getRootDirs(),
        ];
        $content = Render::render('admin/file-move', $data);

        return new Response($content);
    }

    //TODO if force, refresh folder or we might have duplicates displaying in the folder.
    /**
     * Rename or relocate file.
     *
     * @param Request $request
     * @param int    $id
     *
     * @return JsonResponse
     */
    public function renameFile(Request $request, int $id): JsonResponse
    {
        /** @var File */
        $file = ORM::getOne(File::class, $id);
        $pathinfo = pathinfo($file->getPath());

        $dir = $request->request->get('dir', $pathinfo['dirname']);
        $filename = $request->request->get('name', $pathinfo['filename']);
        $filename = $this->cleanFileName($filename);
        $overwrite = $request->request->getBoolean('overwrite');

        $newPath = $dir . '/' . $filename . '.' . $pathinfo['extension'];

        if ($file->getPath() === $newPath) {
            return new JsonResponse(['id' => $id, 'filename' => $filename, 'path' => $file->getPath()]);
        }

        try {
            if (!$filename) {
                throw new InvalidInput(_('The name is invalid.'));
            }

            $this->checkPermittedTargetPath($newPath);

            $existingFile = File::getByPath($newPath);
            if ($existingFile) {
                if ($existingFile->isInUse()) {
                    throw new InvalidInput(_('An in use file already has that name.'));
                }

                if (!$overwrite) {
                    return new JsonResponse([
                        'yesno' =>
                            _('A file with the same name already exists. Would you like to replace the existing file?'),
                        'id' => $id
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
    public function renameFolder(Request $request): JsonResponse
    {
        $path = $request->request->get('path', '');
        $name = $request->request->get('name', '');
        $name = $this->cleanFileName($name);
        $overwrite = $request->request->getBoolean('overwrite');

        $pathinfo = pathinfo($path);
        $newPath = $pathinfo['dirname'] . '/' . $name;

        if ($path === $newPath) {
            return JsonResponse(['filename' => $name, 'path' => $newPath]);
        }

        try {
            if (!$name) {
                throw new InvalidInput(_('The name is invalid.'));
            }

            $this->checkPermittedTargetPath($path);

            if (file_exists(_ROOT_ . $newPath)) {
                if (!$overwrite) {
                    return new JsonResponse(['yesno' =>_('A file with the same name already exists. Would you like to replace the existing file?')]);
                }

                $this->deleteFolder($newPath);
            }

            if (!rename(_ROOT_ . $path, _ROOT_ . $newPath)) {
                throw new InvalidInput(_('An error occurred with the file operations.'));
            }
        } catch (InvalidInput $exception) {
            return new JsonResponse(['error' => $exception->getMessage()]);
        }

        $this->replaceFolderPaths($path, $newPath);

        return new JsonResponse(['filename' => $name, 'path' => $newPath]);
    }

    private function replaceFolderPaths(string $path, string $newPath): void
    {
        $newPathEsc = db()->esc($newPath);
        $pathEsc = db()->esc($path);
        db()->query("UPDATE sider    SET text = REPLACE(text, '=\"" . $pathEsc . "', '=\"" . $newPathEsc . "')");
        db()->query("UPDATE template SET text = REPLACE(text, '=\"" . $pathEsc . "', '=\"" . $newPathEsc . "')");
        db()->query("UPDATE special  SET text = REPLACE(text, '=\"" . $pathEsc . "', '=\"" . $newPathEsc . "')");
        db()->query("UPDATE krav     SET text = REPLACE(text, '=\"" . $pathEsc . "', '=\"" . $newPathEsc . "')");
        db()->query(
            "
            UPDATE files
            SET path = REPLACE(path, '" . $pathEsc . "', '" . $newPathEsc . "')
            WHERE path LIKE '$pathEsc%'
            "
        );
    }

    /**
     * Delete folder
     *
     * @param string $path
     *
     * @return void
     */
    private function deleteFolder(string $path): void
    {
        $files = ORM::getByQuery(
            File::class,
            'SELECT * FROM `' . File::TABLE_NAME . "` WHERE path LIKE '" . db()->esc($path) . "/%'"
        );
        foreach ($files as $file) {
            if ($file->isInUse()) {
                throw new InvalidInput(sprintf(_('"%s" is still in use.'), $file->getPath()));
            }

            $file->delete();
        }

        if (!$this->deltree(_ROOT_ . $path)) {
            throw new InvalidInput(_('A file could not be deleted because it is used on a site.'));
        }
    }

    /**
     * Check that given path is within the permittede datafolders
     *
     * @param string $path
     *
     * @return void
     *
     * @throws InvalidInput
     */
    private function checkPermittedPath(string $path): void
    {
        if (realpath(_ROOT_ . $path) !== _ROOT_ . $path) {
            throw new InvalidInput(_('Path may not be relative.'));
        }

        if (mb_strpos($path . '/', '/files/') !== 0 && mb_strpos($path . '/', '/images/') !== 0) {
            throw new InvalidInput(_('Path is outside of permitted folders.'));
        }
    }

    /**
     * Check that the path is a valid save to taget
     *
     * @param string $path
     *
     * @return void
     *
     * @throws InvalidInput
     */
    private function checkPermittedTargetPath(string $path): void
    {
        $pathinfo = pathinfo($path);
        $this->checkPermittedPath($pathinfo['dirname']);

        if (mb_strlen($path, 'UTF-8') > 255) {
            throw new InvalidInput(_('The name is too long.'));
        }

        if (!is_dir(_ROOT_ . $pathinfo['dirname'] . '/')) {
            throw new InvalidInput(_('Target is not a folder.'));
        }
    }

    /**
     * Check that the given path is a valid save to target
     *
     * @param string $path
     *
     * @return void
     *
     * @throws InvalidInput
     */
    private function checkPathIsAvalible(string $path): void
    {
        $this->checkPermittedTargetPath($path);

        if (file_exists(_ROOT_ . $path)) {
            throw new InvalidInput(_('A file or folder with the same name already exists.'));
        }
    }

    /**
     * Delete a folder structure
     *
     * Alle files must be deleted seperatly
     *
     * return bool
     */
    private function deltree(string $path): bool
    {
        $success = true;

        $nodes = scandir($path);
        foreach ($nodes as $node) {
            if ('.' === $node || '..' === $node) {
                continue;
            }

            if (!is_dir($path . '/' . $node)) {
                return false;
            }

            $success = $success && $this->deltree($path . '/' . $node);
        }
        rmdir($path);

        return $success;
    }

    /**
     * Takes a string and changes it to comply with file name restrictions in windows, linux, mac and urls (UTF8)
     * .|"'´`:%=#&\/+?*<>{}-_
     *
     * @param string $filename
     *
     * @return string
     */
    private function cleanFileName(string $filename): string
    {
        $search = ['/[.&?\/:*"\'´`<>{}|%\s-_=+#\\\\]+/u', '/^\s+|\s+$/u', '/\s+/u'];
        $replace = [' ', '', '-'];

        return mb_strtolower(preg_replace($search, $replace, $filename), 'UTF-8');
    }

    private function filejavascript(File $file): string
    {
        $data = [
            'id'          => $file->getId(),
            'path'        => $file->getPath(),
            'mime'        => $file->getMime(),
            'name'        => pathinfo($file->getPath(), PATHINFO_FILENAME),
            'width'       => $file->getWidth(),
            'height'      => $file->getHeight(),
            'description' => $file->getDescription(),
        ];

        return 'files[' . $file->getId() . '] = new file(' . json_encode($data) . ');';
    }

    private function filehtml(File $file, string $returnType = ''): string
    {
        $html = '';

        $menuType = 'filetile';
        $type = explode('/', $file->getMime());
        $type = array_shift($type);
        if (in_array($file->getMime(), ['image/gif', 'image/jpeg', 'image/png'], true)) {
            $menuType = 'imagetile';
        }
        $html .= '<div id="tilebox' . $file->getId() . '" class="' . $menuType . '"><div class="image"';

        if ('ckeditor' === $returnType) {
            $html .= ' onclick="files[' . $file->getId() . '].addToEditor()"';
        } elseif ('thb' === $returnType && in_array($file->getMime(), ['image/gif', 'image/jpeg', 'image/png'], true)) {
            if ($file->getWidth() <= Config::get('thumb_width')
                && $file->getHeight() <= Config::get('thumb_height')
            ) {
                $html .= ' onclick="insertThumbnail(' . $file->getId() . ')"';
            } else {
                $html .= ' onclick="openImageThumbnail(' . $file->getId() . ')"';
            }
        } else {
            $html .= ' onclick="files[' . $file->getId() . '].openfile();"';
        }

        $html .= '> <img src="';

        $type = explode('/', $file->getMime());
        $type = array_shift($type);
        switch ($file->getMime()) {
            case 'image/gif':
            case 'image/jpeg':
            case 'image/png':
            case 'image/vnd.wap.wbmp':
                $type = 'image-native';
                break;
            case 'application/pdf':
                $type = 'pdf';
                break;
            case 'application/msword':
            case 'application/vnd.ms-excel':
            case 'application/vnd.ms-works':
            case 'application/vnd.oasis.opendocument.graphics':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.shee':
                $type = 'text';
                break;
            case 'application/zip':
                $type = 'zip';
                break;
        }

        switch ($type) {
            case 'image-native':
                $html .= '/admin/image.php?path=' . rawurlencode($file->getPath()) . '&amp;maxW=128&amp;maxH=96';
                break;
            case 'pdf':
            case 'image':
            case 'video':
            case 'audio':
            case 'text':
            case 'zip':
                $html .= '/admin/images/file-' . $type . '.gif';
                break;
            default:
                $html .= '/admin/images/file-bin.gif';
                break;
        }

        $pathinfo = pathinfo($file->getPath());
        $html .= '" alt="" title="" /> </div><div ondblclick="showfilename(' . $file->getId()
            . ')" class="navn" id="navn' . $file->getId() . 'div" title="' . $pathinfo['filename'] . '"> '
            . $pathinfo['filename']
            . '</div><form action="" method="get" onsubmit="document.getElementById(\'files\').focus();return false;" style="display:none" id="navn'
            . $file->getId() . 'form"><p><input onblur="renamefile(\'' . $file->getId() . '\');" maxlength="'
            . (251 - mb_strlen($pathinfo['dirname'], 'UTF-8')) . '" value="' . $pathinfo['filename']
            . '" name="" /></p></form></div>';

        return $html;
    }

    /**
     * Get root of folder tree
     *
     * @return array[]
     */
    private function getRootDirs(): array
    {
        $dirs = [];
        foreach (['/images' => _('Images'), '/files' => _('Files')] as $path => $name) {
            $dirs[] = $this->formatDir($path, $name);
        }

        return $dirs;
    }

    /**
     * Get metadata for a folder
     *
     * @param string $path
     * @param string $name
     *
     * @return array
     */
    private function formatDir(string $path, string $name): array
    {
        $subs = [];
        if (0 === mb_strpos(request()->cookies->get('admin_dir'), $path)) {
            $subs = $this->getSubDirs($path);
            $hassubs = (bool) $subs;
        } else {
            $hassubs = $this->hasSubsDirs($path);
        }

        return [
            'id'      => preg_replace('#/#u', '.', $path),
            'path'    => $path,
            'name'    => $name,
            'hassubs' => $hassubs,
            'subs'    => $subs,
        ];
    }

    /**
     * Return list of folders in a folder.
     *
     * @param string $path
     *
     * @return array[]
     */
    private function getSubDirs(string $path): array
    {
        $dirs = [];
        $folders = glob(_ROOT_ . $path . '/*/');
        foreach ($folders as $folder) {
            $dirs[] = pathinfo($folder, PATHINFO_BASENAME);
        }

        natcasesort($dirs);
        $dirs = array_values($dirs);

        foreach ($dirs as $index => $dir) {
            $dirs[$index] = $this->formatDir($path . '/' . $dir, $dir);
        }

        return $dirs;
    }

    /**
     * Check if folder has subfolders
     *
     * @param string $path
     *
     * @return bool
     */
    private function hasSubsDirs(string $path): bool
    {
        return (bool) glob(_ROOT_ . $path . '/*/');
    }
}
