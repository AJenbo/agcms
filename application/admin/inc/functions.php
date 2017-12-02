<?php

use AGCMS\Config;
use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Email;
use AGCMS\Entity\Invoice;
use AGCMS\Entity\User;
use AGCMS\EpaymentAdminService;
use AGCMS\Exception\Exception;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
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
        throw new Exception('Email ' . $failedCount . '/' . $totalEmails . ' failed to be sent.');
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

/**
 * @param int    $id
 * @param string $html
 * @param string $title
 *
 * @throws Exception
 * @throws InvalidInput
 *
 * @return bool
 */
function updateSpecial(int $id, string $html, string $title = ''): bool
{
    $html = purifyHTML($html);

    /** @var ?CustomPage */
    $page = ORM::getOne(CustomPage::class, $id);
    if (!$page) {
        throw new InvalidInput(_('Page not found'));
    }

    if ($title) {
        $page->setTitle($title);
    }
    $page->setHtml($html)->save();

    if (1 === $id) {
        /** @var ?Category */
        $category = ORM::getOne(Category::class, 0);
        if (!$category) {
            throw new Exception(_('Root category is missing!'));
        }

        $category->setTitle($title)->save();
    }

    return true;
}

function copytonew(int $id): int
{
    $faktura = db()->fetchOne('SELECT * FROM `fakturas` WHERE `id` = ' . $id);

    unset(
        $faktura['id'],
        $faktura['status'],
        $faktura['date'],
        $faktura['paydate'],
        $faktura['sendt'],
        $faktura['transferred']
    );
    $faktura['clerk'] = curentUser()->getFullName();

    $sql = 'INSERT INTO `fakturas` SET';
    foreach ($faktura as $key => $value) {
        $sql .= ' `' . addcslashes($key, '`\\') . "` = '" . db()->esc($value) . "',";
    }
    $sql .= ' `date` = NOW();';

    db()->query($sql);

    return db()->insert_id;
}

/**
 * @return string[]
 */
function save(int $id, string $action, array $updates): array
{
    /** @var ?Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    assert($invoice instanceof Invoice);

    invoiceBasicUpdate($invoice, $action, $updates);

    if ('email' === $action) {
        sendInvoice($invoice);
    }

    return ['type' => $action, 'status' => $invoice->getStatus()];
}

/**
 * @param Invoice $invoice
 * @param string  $action
 * @param array   $updates
 *
 * @throws Exception
 *
 * @return void
 */
function invoiceBasicUpdate(Invoice $invoice, string $action, array $updates): void
{
    $status = $invoice->getStatus();

    if ('new' === $invoice->getStatus()) {
        if ('lock' === $action) {
            $status = 'locked';
        }
        $invoice->setTimeStamp(strtotime($updates['date']));
        $invoice->setShipping($updates['shipping']);
        $invoice->setAmount($updates['amount']);
        $invoice->setVat($updates['vat']);
        $invoice->setPreVat($updates['preVat']);
        $invoice->setIref($updates['iref']);
        $invoice->setEref($updates['eref']);
        $invoice->setName($updates['name']);
        $invoice->setAttn($updates['attn']);
        $invoice->setAddress($updates['address']);
        $invoice->setPostbox($updates['postbox']);
        $invoice->setPostcode($updates['postcode']);
        $invoice->setCity($updates['city']);
        $invoice->setCountry($updates['country']);
        $invoice->setEmail($updates['email']);
        $invoice->setPhone1($updates['phone1']);
        $invoice->setPhone2($updates['phone2']);
        $invoice->setHasShippingAddress($updates['hasShippingAddress']);
        if ($updates['hasShippingAddress']) {
            $invoice->setShippingPhone($updates['shippingPhone']);
            $invoice->setShippingName($updates['shippingName']);
            $invoice->setShippingAttn($updates['shippingAttn']);
            $invoice->setShippingAddress($updates['shippingAddress']);
            $invoice->setShippingAddress2($updates['shippingAddress2']);
            $invoice->setShippingPostbox($updates['shippingPostbox']);
            $invoice->setShippingPostcode($updates['shippingPostcode']);
            $invoice->setShippingCity($updates['shippingCity']);
            $invoice->setShippingCountry($updates['shippingCountry']);
        }
        $invoice->setItemData(json_encode($updates['lines']));
    }

    if (isset($updates['note'])) {
        if ('new' !== $invoice->getStatus()) {
            $updates['note'] = trim($invoice->getNote() . "\n" . $updates['note']);
        }
        $invoice->setNote($updates['note']);
    }

    if (!$invoice->getDepartment() && 1 === count(Config::get('emails'))) {
        $email = first(Config::get('emails'))['address'];
        $invoice->setDepartment($email);
    } elseif (!empty($updates['department'])) {
        $invoice->setDepartment($updates['department']);
    }

    if (!$invoice->getClerk()) {
        $invoice->setClerk(curentUser()->getFullName());
    }

    if (('giro' === $action || 'cash' === $action)
        && in_array($invoice->getStatus(), ['new', 'locked', 'rejected'], true)
    ) {
        $status = $action;
    }

    if (!$invoice->isFinalized()) {
        if (in_array($action, ['cancel', 'giro', 'cash'], true)
            || ('lock' === $action && 'locked' !== $invoice->getStatus())
        ) {
            $invoice->setTimeStampPay(strtotime($updates['paydate'] ?? '') ?: time());
        }

        if ('cancel' === $action) {
            if ('pbsok' === $invoice->getStatus() && !annul($invoice->getId())) {
                throw new Exception(_('Failed to cancel payment!'));
            }
            $status = 'canceled';
        }

        if (isset($updates['clerk']) && curentUser()->hasAccess(User::ADMINISTRATOR)) {
            $invoice->setClerk($updates['clerk']);
        }
    }

    $invoice->setStatus($status)->save();
}

/**
 * @throws InvalidInput
 */
function sendInvoice(Invoice $invoice): void
{
    if (!$invoice->hasValidEmail()) {
        throw new InvalidInput(_('Email is not valid!'));
    }

    if (!$invoice->getDepartment() && 1 === count(Config::get('emails'))) {
        $email = first(Config::get('emails'))['address'];
        $invoice->setDepartment($email);
    } elseif (!$invoice->getDepartment()) {
        throw new InvalidInput(_('You have not selected a sender!'));
    }
    if ($invoice->getAmount() < 0.01) {
        throw new InvalidInput(_('The invoice must be of at at least 0.01 krone!'));
    }

    $subject = _('Online payment for ') . Config::get('site_name');
    $emailTemplate = 'email/invoice';
    if ($invoice->isSent()) {
        $subject = 'Elektronisk faktura vedr. ordre';
        $emailTemplate = 'email/invoice-reminder';
    }

    $emailBody = Render::render(
        $emailTemplate,
        [
            'invoice'  => $invoice,
            'siteName' => Config::get('site_name'),
            'address'  => Config::get('address'),
            'postcode' => Config::get('postcode'),
            'city'     => Config::get('city'),
            'phone'    => Config::get('phone'),
        ]
    );

    $email = new Email([
        'subject'          => $subject,
        'body'             => $emailBody,
        'senderName'       => Config::get('site_name'),
        'senderAddress'    => $invoice->getDepartment(),
        'recipientName'    => $invoice->getName(),
        'recipientAddress' => $invoice->getEmail(),
    ]);

    $emailService = new EmailService();
    $emailService->send($email);

    if ('new' === $invoice->getStatus()) {
        $invoice->setStatus('locked');
    }

    $invoice->setSent(true)
        ->save();
}

/**
 * @throws InvalidInput
 *
 * @return string[]
 */
function sendReminder(int $id): array
{
    /** @var ?Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    if (!$invoice) {
        throw new InvalidInput(_('Email is not valid!'));
    }

    sendInvoice($invoice);

    throw new InvalidInput(_('A Reminder was sent to the customer.'));
}

/**
 * @throws Exception
 * @throws InvalidInput
 *
 * @return string[]|true
 */
function pbsconfirm(int $id)
{
    /** @var ?Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    if (!$invoice) {
        throw new InvalidInput(_('Email is not valid!'));
    }

    $epaymentService = new EpaymentAdminService(Config::get('pbsid'), Config::get('pbspwd'));
    $epayment = $epaymentService->getPayment(Config::get('pbsfix') . $invoice->getId());
    if (!$epayment->confirm()) {
        throw new Exception(_('An error occurred'));
    }

    $invoice->setStatus('accepted')
        ->setTimeStampPay(time())
        ->save();

    return true;
}

/**
 * @throws Exception
 * @throws InvalidInput
 *
 * @return string[]|true
 */
function annul(int $id)
{
    /** @var ?Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    if (!$invoice) {
        throw new InvalidInput(_('Email is not valid!'));
    }

    $epaymentService = new EpaymentAdminService(Config::get('pbsid'), Config::get('pbspwd'));
    $epayment = $epaymentService->getPayment(Config::get('pbsfix') . $invoice->getId());
    if (!$epayment->annul()) {
        throw new Exception(_('An error occurred'));
    }

    if ('pbsok' === $invoice->getStatus()) {
        $invoice->setStatus('rejected')->save();
    }

    return true;
}
