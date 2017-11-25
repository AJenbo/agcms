<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Category;
use AGCMS\Entity\Contact;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Email;
use AGCMS\Entity\File;
use AGCMS\Entity\Page;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Service\EmailService;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * Remove newletter submissions that are missing vital information.
     *
     * @return JsonResponse
     */
    public function removeBadContacts(): JsonResponse
    {
        $contacts = ORM::getByQuery(
            Contact::class,
            "SELECT * FROM `email` WHERE `email` = '' AND `adresse` = '' AND `tlf1` = '' AND `tlf2` = ''"
        );
        foreach ($contacts as $contact) {
            $contact->delete();
        }

        return new JsonResponse(['count' => count($contacts)]);
    }

    /**
     * Get a list of pages with no bindings.
     *
     * @return JsonResponse
     */
    public function orphanPages(): JsonResponse
    {
        $pages = ORM::getByQuery(Page::class, 'SELECT * FROM `sider` WHERE `id` NOT IN(SELECT `side` FROM `bind`)');

        $html = '';
        if ($pages) {
            $html = '<b>' . _('The following pages have no binding') . '</b><br />';
            foreach ($pages as $page) {
                /* @var Page */
                assert($page instanceof Page);
                $html .= '<a href="?side=redigerside&amp;id=' . $page->getId() . '">' . $page->getId()
                    . ': ' . $page->getTitle() . '</a><br />';
            }
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Get list of pages with bindings to both active and inactive sections of the site.
     *
     * @return JsonResponse
     */
    public function mismatchedBindings(): JsonResponse
    {
        $html = '';

        // Map out active / inactive
        $categoryActiveMaps = [];
        $categories = ORM::getByQuery(Category::class, 'SELECT * FROM `kat`');
        foreach ($categories as $category) {
            assert($category instanceof Category);
            $categoryActiveMaps[(int) $category->isInactive()][] = $category->getId();
        }

        $pages = ORM::getByQuery(
            Page::class,
            '
            SELECT * FROM `sider`
            WHERE EXISTS (
                SELECT * FROM bind
                WHERE side = sider.id
                AND kat IN (' . implode(',', $categoryActiveMaps[0]) . ')
            )
            AND EXISTS (
                SELECT * FROM bind
                WHERE side = sider.id
                AND kat IN (' . implode(',', $categoryActiveMaps[1]) . ')
            )
            ORDER BY id
            '
        );
        if ($pages) {
            $html .= '<b>' . _('The following pages are both active and inactive') . '</b><br />';
            foreach ($pages as $page) {
                assert($page instanceof Page);
                $html .= '<a href="?side=redigerside&amp;id=' . $page->getId() . '">' . $page->getId() . ': '
                    . $page->getTitle() . '</a><br />';
            }
        }

        //Add active pages that has a list that links to this page
        $pages = db()->fetchArray(
            '
            SELECT `sider`.*, `lists`.`page_id`
            FROM `list_rows`
            JOIN `lists` ON `list_rows`.`list_id` = `lists`.`id`
            JOIN `sider` ON `list_rows`.`link` = `sider`.id
            WHERE EXISTS (
                SELECT * FROM bind
                WHERE side = `lists`.`page_id`
                AND kat IN (' . implode(',', $categoryActiveMaps[0]) . ')
            )
            AND EXISTS (
                SELECT * FROM bind
                WHERE side = sider.id
                AND kat IN (' . implode(',', $categoryActiveMaps[1]) . ')
            )
            ORDER BY `lists`.`page_id`
            '
        );
        if ($pages) {
            $html .= '<b>' . _('The following inactive pages appears in list on active pages') . '</b><br />';
            foreach ($pages as $page) {
                $listPage = ORM::getOne(Page::class, $page['page_id']);
                assert($listPage instanceof Page);
                $page = new Page(Page::mapFromDB($page));
                $html .= '<a href="?side=redigerside&amp;id=' . $listPage->getId() . '">' . $listPage->getId() . ': '
                    . $listPage->getTitle() . '</a> -&gt; <a href="?side=redigerside&amp;id=' . $page->getId() . '">'
                    . $page->getId() . ': ' . $page->getTitle() . '</a><br />';
            }
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * List categories that have been circularly linked.
     *
     * @return JsonResponse
     */
    public function circularLinks(): JsonResponse
    {
        $html = '';
        $categories = ORM::getByQuery(Category::class, 'SELECT * FROM `kat` WHERE bind != 0 AND bind != -1');
        foreach ($categories as $category) {
            assert($category instanceof Category);
            $branchIds = [$category->getId() => true];
            while ($category = $category->getParent()) {
                if (isset($branchIds[$category->getId()])) {
                    $html .= '<a href="/admin/categories/' . $category->getId() . '/">' . $category->getId()
                        . ': ' . $category->getTitle() . '</a><br />';
                    break;
                }
                $branchIds[$category->getId()] = true;
            }
        }
        if ($html) {
            $html = '<b>' . _('The following categories have circular references:') . '</b><br />' . $html;
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Get list of files with problematic names.
     *
     * @return JsonResponse
     */
    public function badFileNames(): JsonResponse
    {
        $files = ORM::getByQuery(
            File::class,
            '
            SELECT * FROM `files`
            WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+[^/]+$\'
            ORDER BY `path` ASC
            '
        );
        $html = '';
        if ($files) {
            $msg = ngettext(
                'The following %d file must be renamed:',
                'The following %d files must be renamed:',
                count($files)
            );
            $html = '<br /><b>' . sprintf($msg, count($files))
                . '</b><br /><br /><br /><a onclick="explorer(\'\',\'\');">';
            foreach ($files as $file) {
                $html .= $file->getPath() . '<br />';
            }
            $html .= '</a>';
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Get list of bad folder names.
     *
     * @return JsonResponse
     */
    public function badFolderNames(): JsonResponse
    {
        $html = '';
        $errors = db()->fetchArray(
            '
            SELECT path FROM `files`
            WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+.*[/]+\'
            ORDER BY `path` ASC
            '
        );
        if ($errors) {
            $msg = ngettext(
                'The following %d file is in a folder that needs to be renamed:',
                'The following %d files are in folders that needs to be renamed:',
                count($errors)
            );
            $html .= '<br /><b>' . sprintf($msg, count($errors)) . '</b><br /><a onclick="explorer(\'\',\'\');">';
            //TODO only repport one error per folder
            foreach ($errors as $value) {
                $html .= $value['path'] . '<br />';
            }
            $html .= '</a>';
        }
        if ($html) {
            $html = '<b>' . _('The following folders must be renamed') . '</b><br />' . $html;
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Endpoint for getting size of files.
     *
     * @return JsonResponse
     */
    public function sizeOfFiles(): JsonResponse
    {
        return new JsonResponse(['size' => $this->getSizeOfFiles()]);
    }

    /**
     * Resend any email that failed ealier.
     *
     * @return JsonResponse
     */
    public function sendDelayedEmail(): JsonResponse
    {
        $cronStatus = ORM::getOne(CustomPage::class, 0);
        assert($cronStatus instanceof CustomPage);

        $html = '';

        //Get emails that needs sending
        $emails = ORM::getByQuery(Email::class, 'SELECT * FROM `emails`');
        if ($emails) {
            $emailsSendt = 0;
            $emailService = new EmailService();
            foreach ($emails as $email) {
                $emailService->send($email);
                $email->delete();
                ++$emailsSendt;
            }

            $cronStatus->save();

            $msg = ngettext(
                '%d of %d e-mail was sent.',
                '%d of %d e-mails was sent.',
                $emailsSendt
            );
            $html = sprintf($msg, $emailsSendt, count($emails));
        }

        return new JsonResponse(['html' => $html]);
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
