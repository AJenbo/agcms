<?php namespace App\Http\Controllers\Admin;

use AJenbo\Imap;
use App\Application;
use App\Exceptions\Exception;
use App\Models\Category;
use App\Models\Contact;
use App\Models\CustomPage;
use App\Models\Email;
use App\Models\File;
use App\Models\Page;
use App\Services\DbService;
use App\Services\EmailService;
use App\Services\OrmService;
use App\Services\RenderService;
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
     * @throws Exception
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        /** @var DbService */
        $db = app(DbService::class);

        $db->addLoadedTable('emails');
        $emailStatus = $db->fetchArray("SHOW TABLE STATUS LIKE 'emails'");
        /** @var (string|int)[] */
        $emailStatus = reset($emailStatus);

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?CustomPage */
        $page = $orm->getOne(CustomPage::class, 0);
        if (!$page) {
            throw new Exception(_('Cron status missing'));
        }

        $db->addLoadedTable('emails');
        $data = [
            'dbSize'             => $this->byteToHuman($this->getDbSize()),
            'wwwSize'            => $this->byteToHuman($this->getSizeOfFiles()),
            'pendingEmails'      => $db->fetchOne("SELECT count(*) as 'count' FROM `emails`")['count'],
            'totalDelayedEmails' => (int) $emailStatus['Auto_increment'] - 1,
            'lastrun'            => $page->getTimeStamp(),
        ] + $this->basicPageData($request);

        return $this->render('admin/get_db_error', $data);
    }

    /**
     * Format bytes in a hum frindly maner.
     *
     * @param int $size
     *
     * @return string
     */
    private function byteToHuman(int $size): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB', 'BiB'];
        foreach ($units as $unit) {
            if ($size < 1024 || 'BiB' === $unit) {
                break;
            }

            $size /= 1024;
        }

        return number_format($size, 1, localeconv()['mon_decimal_point'], '') . $unit;
    }

    /**
     * Remove newletter submissions that are missing vital information.
     *
     * @return JsonResponse
     */
    public function removeBadContacts(): JsonResponse
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var Contact[] */
        $contacts = $orm->getByQuery(
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
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var Page[] */
        $pages = $orm->getByQuery(
            Page::class,
            'SELECT * FROM `sider` WHERE `id` NOT IN(SELECT `side` FROM `bind`)'
        );

        $html = '';
        if ($pages) {
            $html = '<b>' . _('The following pages have no binding') . '</b><br />';
            foreach ($pages as $page) {
                $html .= '<a href="/admin/page/' . $page->getId() . '/">' . $page->getId()
                    . ': ' . $page->getTitle() . '</a><br />';
            }
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Get list of pages with bindings to both active and inactive sections of the site.
     *
     * @throws Exception
     *
     * @return JsonResponse
     */
    public function mismatchedBindings(): JsonResponse
    {
        $html = '';

        // Map out active / inactive
        $categoryActiveMaps = [];

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var Category[] */
        $categories = $orm->getByQuery(Category::class, 'SELECT * FROM `kat`');
        foreach ($categories as $category) {
            $categoryActiveMaps[(int) $category->isInactive()][] = $category->getId();
        }

        /** @var Page[] */
        $pages = $orm->getByQuery(
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
                $html .= '<a href="/admin/page/' . $page->getId() . '/">' . $page->getId() . ': '
                    . $page->getTitle() . '</a><br />';
            }
        }

        /** @var DbService */
        $db = app(DbService::class);

        //Add active pages that has a list that links to this page
        $db->addLoadedTable('list_rows', 'lists', 'sider', 'bind');
        $pages = $db->fetchArray(
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
            $html .= '<b>' . _('The following inactive pages appear in a list on an active page:') . '</b><br />';
            foreach ($pages as $page) {
                /** @var ?Page */
                $listPage = $orm->getOne(Page::class, (int)$page['page_id']);
                if (!$listPage) {
                    throw new Exception(_('Page disappeared during processing'));
                }

                unset($page['page_id']);
                $page = new Page(Page::mapFromDB($page));
                $html .= '<a href="/admin/page/' . $listPage->getId() . '/">' . $listPage->getId() . ': '
                    . $listPage->getTitle() . '</a> -&gt; <a href="/admin/page/' . $page->getId() . '/">'
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

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var Category[] */
        $categories = $orm->getByQuery(Category::class, 'SELECT * FROM `kat` WHERE bind != 0 AND bind != -1');
        foreach ($categories as $category) {
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
     * Remove enteries for files that do no longer exist.
     *
     * @return JsonResponse
     */
    public function removeNoneExistingFiles(): JsonResponse
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var File[] */
        $files = $orm->getByQuery(File::class, 'SELECT * FROM `files`');

        /** @var Application */
        $app = app();

        $deleted = 0;
        $missingFiles = [];
        foreach ($files as $file) {
            if (!is_file($app->basePath($file->getPath()))) {
                if (!$file->isInUse()) {
                    $file->delete();
                    $deleted++;
                    continue;
                }

                $missingFiles[] = $file->getPath();
            }
        }

        return new JsonResponse(['missingFiles' => $missingFiles, 'deleted' => $deleted]);
    }

    /**
     * Get list of files with problematic names.
     *
     * @return JsonResponse
     */
    public function badFileNames(): JsonResponse
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var File[] */
        $files = $orm->getByQuery(
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
                . '</b><br /><br /><br /><a onclick="explorer(\'\',\'\')">';
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
     * @todo only repport one error per folder
     *
     * @return JsonResponse
     */
    public function badFolderNames(): JsonResponse
    {
        /** @var DbService */
        $db = app(DbService::class);

        $db->addLoadedTable('files');
        $html = '';
        $errors = $db->fetchArray(
            '
            SELECT path FROM `files`
            WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+.*[/]+\'
            ORDER BY `path` ASC
            '
        );
        if ($errors) {
            $msg = ngettext(
                'The following %d file is in a folder that needs to be renamed:',
                'The following %d files are in a folder that needs to be renamed:',
                count($errors)
            );
            $html .= '<br /><b>' . sprintf($msg, count($errors)) . '</b><br /><a onclick="explorer(\'\',\'\')">';
            foreach ($errors as $value) {
                $html .= $value['path'] . '<br />';
            }
            $html .= '</a>';
        }
        if ($html) {
            $html = '<b>' . _('The following folders must be renamed:') . '</b><br />' . $html;
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Endpoint for getting system usage.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function usage(Request $request): JsonResponse
    {
        return new JsonResponse([
            'www' => $this->getSizeOfFiles(),
            'db'  => $this->getDbSize(),
        ]);
    }

    /**
     * Resend any email that failed ealier.
     *
     * @throws Exception
     *
     * @return JsonResponse
     */
    public function sendDelayedEmail(): JsonResponse
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?CustomPage */
        $cronStatus = $orm->getOne(CustomPage::class, 0);
        if (!$cronStatus) {
            throw new Exception(_('Cron status missing'));
        }

        $html = '';

        /** @var Email[] */
        $emails = $orm->getByQuery(Email::class, 'SELECT * FROM `emails`');
        if ($emails) {
            $emailsSendt = 0;
            /** @var EmailService */
            $emailService = app(EmailService::class);
            foreach ($emails as $email) {
                $emailService->send($email);
                $email->delete();
                $emailsSendt++;
            }

            $cronStatus->save();

            $msg = ngettext(
                '%d of %d email was sent.',
                '%d of %d emails were sent.',
                $emailsSendt
            );
            $html = sprintf($msg, $emailsSendt, count($emails));
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Get list of contacts with invalid emails.
     *
     * @return JsonResponse
     */
    public function contactsWithInvalidEmails(): JsonResponse
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var Contact[] */
        $contacts = $orm->getByQuery(Contact::class, "SELECT * FROM `email` WHERE `email` != ''");
        foreach ($contacts as $key => $contact) {
            if ($contact->isEmailValide()) {
                unset($contacts[$key]);
            }
        }

        /** @var RenderService */
        $render = app(RenderService::class);

        $html = $render->render('admin/partial-subscriptions_with_bad_emails', ['contacts' => $contacts]);

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Get combined email usage.
     *
     * @return JsonResponse
     */
    public function mailUsage(): JsonResponse
    {
        $size = 0;

        foreach (config('emails', []) as $email) {
            $imap = new Imap(
                $email['address'],
                $email['password'],
                $email['imapHost'],
                $email['imapPort']
            );

            foreach ($imap->listMailboxes() as $mailbox) {
                $mailboxStatus = $imap->select($mailbox['name'], true);
                if (!$mailboxStatus['exists']) {
                    continue;
                }

                $mails = $imap->fetch('1:*', 'RFC822.SIZE');
                preg_match_all('/RFC822.SIZE\s([0-9]+)/', $mails['data'], $mailSizes);
                $size += array_sum($mailSizes[1]);
            }
        }

        return new JsonResponse(['size' => $size]);
    }

    /**
     * Get size of database.
     *
     * @return int
     */
    private function getDbSize(): int
    {
        /** @var DbService */
        $db = app(DbService::class);

        $tabels = $db->fetchArray('SHOW TABLE STATUS');
        $dbsize = 0;
        foreach ($tabels as $tabel) {
            $dbsize += $tabel['Data_length'];
            $dbsize += $tabel['Index_length'];
        }

        return (int) $dbsize;
    }

    /**
     * Get total size of files.
     *
     * @return int
     */
    private function getSizeOfFiles(): int
    {
        /** @var DbService */
        $db = app(DbService::class);

        $db->addLoadedTable('files');
        $files = $db->fetchOne('SELECT sum(`size`) AS `filesize` FROM `files`');

        return (int)($files['filesize'] ?? 0);
    }
}
