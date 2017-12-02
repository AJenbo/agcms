<?php

use AGCMS\Config;
use AGCMS\Entity\Email;
use AGCMS\Exception\Exception;
use AGCMS\Exception\InvalidInput;
use AGCMS\Render;
use AGCMS\Service\EmailService;
use Throwable;

/**
 * Remove enteries for files that do no longer exist.
 *
 * @return string Always empty
 */
function removeNoneExistingFiles(): string
{
    $files = db()->fetchArray('SELECT id, path FROM `files`');

    $missing = [];
    foreach ($files as $files) {
        if (!is_file(app()->basePath($files['path']))) {
            $missing[] = (int) $files['id'];
        }
    }
    if ($missing) {
        db()->query('DELETE FROM `files` WHERE `id` IN(' . implode(',', $missing) . ')');
    }

    return '';
}

/**
 * @todo resend failed emails, save bcc
 *
 * @throws Exception
 * @throws InvalidInput
 *
 * @return string[]|true
 */
function sendEmail(
    int $id,
    string $from,
    string $interests,
    string $subject,
    string $html
) {
    if (!db()->fetchArray('SELECT `id` FROM `newsmails` WHERE `sendt` = 0')) {
        //Nyhedsbrevet er allerede afsendt!
        throw new InvalidInput(_('The newsletter has already been sent!'));
    }

    saveEmail($from, $interests, $subject, $html, $id);

    $html = purifyHTML($html);

    //Colect interests
    $andwhere = '';
    if ($interests) {
        $interests = explode('<', $interests);
        foreach ($interests as $interest) {
            if ($andwhere) {
                $andwhere .= ' OR ';
            }
            $andwhere .= '`interests` LIKE \'';
            $andwhere .= $interest;
            $andwhere .= '\' OR `interests` LIKE \'';
            $andwhere .= $interest;
            $andwhere .= '<%\' OR `interests` LIKE \'%<';
            $andwhere .= $interest;
            $andwhere .= '\' OR `interests` LIKE \'%<';
            $andwhere .= $interest;
            $andwhere .= '<%\'';
        }
        $andwhere = ' AND (' . $andwhere;
        $andwhere .= ')';
    }

    $emails = db()->fetchArray(
        'SELECT navn, email
        FROM `email`
        WHERE `email` NOT LIKE \'\'
          AND `kartotek` = \'1\' ' . $andwhere . '
        GROUP BY `email`'
    );
    $totalEmails = count($emails);
    $emailsGroup = [];
    foreach ($emails as $x => $email) {
        $emailsGroup[(int) floor($x / 99) + 1][] = $email;
    }

    $data = [
        'siteName' => Config::get('site_name'),
        'css'      => file_get_contents(
            app()->basePath('/theme/' . Config::get('theme', 'default') . '/style/email.css')
        ),
        'body'     => str_replace(' href="/', ' href="' . Config::get('base_url') . '/', $html),
    ];
    $emailService = new EmailService();
    $failedCount = 0;
    foreach ($emailsGroup as $bcc) {
        $email = new Email([
            'subject'          => $subject,
            'body'             => Render::render('email/newsletter', $data),
            'senderName'       => Config::get('site_name'),
            'senderAddress'    => $from,
            'recipientName'    => Config::get('site_name'),
            'recipientAddress' => $from,
        ]);

        try {
            $emailService->send($email, $bcc);
        } catch (Throwable $exception) {
            app()->logException($exception);
            $failedCount += count($bcc);
        }
    }
    if ($failedCount) {
        throw new Exception(sprintf(_('Email %d/%d failed to be sent.'), $failedCount, $totalEmails));
    }

    db()->query('UPDATE `newsmails` SET `sendt` = 1 WHERE `id` = ' . $id);

    return true;
}

function countEmailTo(array $interests): int
{
    //Colect interests
    $andwhere = '';
    if ($interests) {
        foreach ($interests as $interest) {
            if ($andwhere) {
                $andwhere .= ' OR ';
            }
            $andwhere .= '`interests` LIKE \'';
            $andwhere .= $interest;
            $andwhere .= '\' OR `interests` LIKE \'';
            $andwhere .= $interest;
            $andwhere .= '<%\' OR `interests` LIKE \'%<';
            $andwhere .= $interest;
            $andwhere .= '\' OR `interests` LIKE \'%<';
            $andwhere .= $interest;
            $andwhere .= '<%\'';
        }
        $andwhere = ' AND (' . $andwhere . ')';
    }

    $emails = db()->fetchOne(
        "
        SELECT count(DISTINCT email) as 'count'
        FROM `email`
        WHERE `email` NOT LIKE '' AND `kartotek` = '1'
        " . $andwhere
    );

    return $emails['count'];
}

function saveEmail(string $from, string $interests, string $subject, string $html, int $id = null): bool
{
    $html = purifyHTML($html);

    if (null === $id) {
        db()->query(
            "
            INSERT INTO `newsmails` (`from`, `interests`, `subject`, `text`)
            VALUES (
                '" . db()->esc($from) . "',
                '" . db()->esc($interests) . "',
                '" . db()->esc($subject) . "',
                '" . db()->esc($html) . "'
            )
            "
        );

        return true;
    }

    db()->query(
        "UPDATE `newsmails`
        SET `from` = '" . db()->esc($from) . "',
        `interests` = '" . db()->esc($interests) . "',
        `subject` = '" . db()->esc($subject) . "',
        `text` = '" . db()->esc($html) . "'
        WHERE `id` = " . $id
    );

    return true;
}

function makeNewList(string $navn): array
{
    db()->query('INSERT INTO `tablesort` (`navn`) VALUES (\'' . db()->esc($navn) . '\')');

    return ['id' => db()->insert_id, 'name' => $navn];
}

function saveListOrder(int $id, string $navn, string $text): bool
{
    db()->query(
        'UPDATE `tablesort` SET navn = \'' . db()->esc($navn) . '\', text = \'' . db()->esc($text) . '\'
        WHERE id = ' . $id
    );

    return true;
}

function sogogerstat(string $sog, string $erstat): int
{
    db()->query('UPDATE sider SET text = REPLACE(text,\'' . db()->esc($sog) . '\',\'' . db()->esc($erstat) . '\')');

    return db()->affected_rows;
}

function emaillist()
{
    $data = [];
    $data['newsletters'] = db()->fetchArray(
        'SELECT id, subject, sendt sent FROM newsmails ORDER BY sendt, id DESC'
    );
}

function viewemail(Request $request)
{
    $data = [];
    $id = (int) $request->get('id', 0);
    $data['recipientCount'] = 0;
    if ($id) {
        $data['newsletter'] = db()->fetchOne(
            'SELECT id, sendt sent, `from`, interests, subject, text html FROM newsmails WHERE id = ' . $id
        );
        $data['newsletter']['interests'] = explode('<', $data['newsletter']['interests']);
    }
    $data['recipientCount'] = countEmailTo($data['newsletter']['interests'] ?? []);
    $data['interests'] = Config::get('interests', []);
    $data['textWidth'] = Config::get('text_width');
    $data['emails'] = array_keys(Config::get('emails'));
}

function listsort()
{
    $data = [];
    $data['lists'] = db()->fetchArray('SELECT id, navn FROM `tablesort`');
}

function listsortEdit(Request $request)
{
    // listsort-edit
    $data = [];
    $id = (int) $request->get('id', 0);
    if ($id) {
        $list = db()->fetchOne('SELECT * FROM `tablesort` WHERE `id` = ' . $id);
        $data = [
            'id'        => $id,
            'name'      => $list['navn'],
            'rows'      => explode('<', $list['text']),
            'textWidth' => Config::get('text_width'),
        ] + $data;
    }
}
