<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\File;
use AGCMS\Config;
use AGCMS\Render;
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
