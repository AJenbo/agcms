<?php namespace AGCMS\Controller\Admin;

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
     * @param string $path
     * @param bool   $move
     *
     * @return JsonResponse
     */
    public function subFolders(Request $request): JsonResponse
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
