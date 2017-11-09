<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\File;
use AGCMS\Config;
use AGCMS\Render;
use AGCMS\ORM;
use DirectoryIterator;
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
            'returnid'   => request()->get('returnid'),
            'bgcolor'    => Config::get('bgcolor'),
            'dirs'       => $this->getRootDirs(),
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
     * display a list of files in the selected folder.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function files(Request $request): JsonResponse
    {
        $dir = $request->get('path');
        $html = '';
        $javascript = '';

        $files = scandir(_ROOT_ . $dir);
        natcasesort($files);

        foreach ($files as $fileName) {
            if ('.' === mb_substr($fileName, 0, 1) || is_dir(_ROOT_ . $dir . '/' . $fileName)) {
                continue;
            }

            $filePath = $dir . '/' . $fileName;
            $file = File::getByPath($filePath);
            if (!$file) {
                $file = File::fromPath($filePath)->save();
            }

            $html .= $this->filehtml($file);
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
                $html .= $this->filehtml($file);
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

        if ($file->isinuse()) {
            return new JsonResponse(['error' => _('The file can not be deleted because it is in use.')]);
        }

        if (!$file->delete()) {
            return new JsonResponse(['error' => _('Unable to delete file.')]);
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
        $name = $this->cleanFileName($request->get('name', ''));
        $path = _ROOT_ . $request->get('path', '');
        if (realpath($path) !== $path) {
            return new JsonResponse(['error' => _('Invalid path.')]);
        }

        if (file_exists($path . '/' . $name)) {
            return new JsonResponse(['error' => _('A file or folder with the same name already exists.')]);
        }

        if (!@mkdir($path . '/' . $name, 0771)) {
            return new JsonResponse(
                ['error' => _('Could not create folder, you may not have sufficient rights to this folder.')]
            );
        }

        return new JsonResponse(['error' => false]);
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

    private function filehtml(File $file): string
    {
        $html = '';

        $menuType = 'filetile';
        $type = explode('/', $file->getMime());
        $type = array_shift($type);
        if (in_array($file->getMime(), ['image/gif', 'image/jpeg', 'image/png'], true)) {
            $menuType = 'imagetile';
        }
        $html .= '<div id="tilebox' . $file->getId() . '" class="' . $menuType . '"><div class="image"';

        $returnType = request()->get('return');
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
        $html .= '" alt="" title="" /> </div><div ondblclick="showfilename(' . $file->getId() . ')" class="navn" id="navn'
        . $file->getId() . 'div" title="' . $pathinfo['filename'] . '"> ' . $pathinfo['filename']
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
        $iterator = new DirectoryIterator(_ROOT_ . $path);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDot() || !$fileinfo->isDir()) {
                continue;
            }
            $dirs[] = $fileinfo->getFilename();
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
        $iterator = new DirectoryIterator(_ROOT_ . $path);
        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isDot() && $fileinfo->isDir()) {
                return true;
            }
        }

        return false;
    }
}
