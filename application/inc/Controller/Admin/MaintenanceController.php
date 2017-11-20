<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\CustomPage;
use AGCMS\ORM;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @todo test for missing alt="" in <img>
 */
class MaintenanceController extends AbstractAdminController
{
    /**
     * Create or edit category.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $emailStatus = db()->fetchArray("SHOW TABLE STATUS LIKE 'emails'");
        $emailStatus = reset($emailStatus);
        $page = ORM::getOne(CustomPage::class, 0);
        assert($page instanceof CustomPage);
        $data = [
            'dbSize'             => $this->getDbSize() / 1024 / 1024,
            'wwwSize'            => $this->getSizeOfFiles() / 1024 / 1024,
            'pendingEmails'      => db()->fetchOne("SELECT count(*) as 'count' FROM `emails`")['count'],
            'totalDelayedEmails' => $emailStatus['Auto_increment'] - 1,
            'lastrun'            => $page->getTimeStamp(),
        ] + $this->basicPageData($request);

        $content = Render::render('admin/get_db_error', $data);

        return new Response($content);
    }

    /**
     * Get size of database.
     *
     * @return int
     */
    private function getDbSize(): int
    {
        $tabels = db()->fetchArray('SHOW TABLE STATUS');
        $dbsize = 0;
        foreach ($tabels as $tabel) {
            $dbsize += $tabel['Data_length'];
            $dbsize += $tabel['Index_length'];
        }

        return $dbsize;
    }

    /**
     * Get total size of files.
     *
     * @return int
     */
    private function getSizeOfFiles(): int
    {
        $files = db()->fetchOne('SELECT sum(`size`) AS `filesize` FROM `files`');

        return $files['filesize'] ?? 0;
    }
}
