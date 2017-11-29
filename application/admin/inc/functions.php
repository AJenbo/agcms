<?php

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Email;
use AGCMS\Entity\File;
use AGCMS\Entity\Invoice;
use AGCMS\Entity\Page;
use AGCMS\Entity\User;
use AGCMS\EpaymentAdminService;
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
        if (!is_file(_ROOT_ . $files['path'])) {
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
 * @throws InvalidInput
 * @throws Exception
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
        'css'      => file_get_contents(_ROOT_ . '/theme/' . Config::get('theme', 'default') . '/style/email.css'),
        'body'     => str_replace(' href="/', ' href="' . Config::get('base_url') . '/', $html),
    ];
    $emailService = new EmailService();
    $failedCount = 0;
    foreach ($emailsGroup as $of => $bcc) {
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
            Application::getInstance()->logException($exception);
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

/**
 * Delete unused file.
 *
 * @throws InvalidInput
 *
 * @return int[]|string[]
 */
function deletefile(int $id, string $path): array
{
    if (isinuse($path)) {
        throw new InvalidInput(_('The file can not be deleted because it is used on a page.'));
    }
    $file = File::getByPath($path);
    if ($file) {
        $file->delete();
    }

    return ['id' => $id];
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

/**
 * @throws InvalidInput
 *
 * @return string[]|true
 */
function save_ny_kat(string $navn, int $kat, int $vis, string $email, int $iconId = null)
{
    if (!$navn) {
        throw new InvalidInput(_('You must enter a name and choose a location for the new category.'));
    }

    $category = new Category([
        'title'             => $navn,
        'parent_id'         => $kat,
        'icon_id'           => $iconId,
        'render_mode'       => $vis,
        'email'             => $email,
        'weighted_children' => 0,
        'weight'            => 0,
    ]);
    $category->save();

    return true;
}

function sogogerstat(string $sog, string $erstat): int
{
    db()->query('UPDATE sider SET text = REPLACE(text,\'' . db()->esc($sog) . '\',\'' . db()->esc($erstat) . '\')');

    return db()->affected_rows;
}

/**
 * @param int    $id
 * @param string $navn
 * @param string $link
 * @param int    $iconId
 *
 * @throws InvalidInput
 *
 * @return array
 */
function updatemaerke(?int $id, string $navn, string $link = '', int $iconId = null): array
{
    if (!$navn) {
        throw new InvalidInput(_('You must enter a name.'));
    }

    $brand = new Brand(['title' => $navn, 'link' => $link, 'icon_id' => $iconId]);
    if (null !== $id) {
        $icon = null;
        if (null !== $iconId) {
            $icon = ORM::getOne(File::class, $iconId);
        }

        $brand = ORM::getOne(Brand::class, $id);
        assert($brand instanceof Brand);
        $brand->setIcon($icon)
            ->setLink($link)
            ->setTitle($navn);
    }
    $brand->save();

    return ['id' => $brand->getId()];
}

/**
 * @return string[]
 */
function sletmaerke(int $id): array
{
    db()->query('DELETE FROM `maerke` WHERE `id` = ' . $id);

    return ['node' => 'maerke' . $id];
}

/**
 * @param int $pageId
 * @param int $categoryId
 *
 * @throws InvalidInput
 *
 * @return array
 */
function sletbind(int $pageId, int $categoryId): array
{
    /** @var ?Page */
    $page = ORM::getOne(Page::class, $pageId);
    assert($page instanceof Page);

    /** @var ?Category */
    $category = ORM::getOne(Category::class, $categoryId);
    if (!$category) {
        throw new InvalidInput(_('The category doesn\'t exist.'));
    }
    assert($category instanceof Category);

    $result = ['pageId' => $page->getId(), 'deleted' => [], 'added' => null];
    if (($category->getId() === -1 && 1 === count($page->getCategories()))
        || !$page->isInCategory($category)
    ) {
        return $result;
    }

    if (1 === count($page->getCategories())) {
        $inactiveCategory = ORM::getOne(Category::class, -1);
        assert($inactiveCategory instanceof Category);
        $page->addToCategory($inactiveCategory);
        $result['added'] = ['categoryId' => -1, 'path' => '/' . _('Inactive') . '/'];
    }

    $page->removeFromCategory($category);
    $result['deleted'][] = $category->getId();

    return $result;
}

/**
 * @param int $pageId
 * @param int $categoryId
 *
 * @throws InvalidInput
 *
 * @return array
 */
function bind(int $pageId, int $categoryId): array
{
    /** @var ?Page */
    $page = ORM::getOne(Page::class, $pageId);
    assert($page instanceof Page);

    /** @var ?Category */
    $category = ORM::getOne(Category::class, $categoryId);
    if (!$category) {
        throw new InvalidInput(_('The category doesn\'t exist.'));
    }
    assert($category instanceof Category);

    $result = ['pageId' => $page->getId(), 'deleted' => [], 'added' => null];

    if ($page->isInCategory($category)) {
        return $result;
    }

    $page->addToCategory($category);
    $result['added'] = ['categoryId' => $category->getId(), 'path' => $category->getPath()];

    $rootCategory = $category->getRoot();
    foreach ($page->getCategories() as $node) {
        if ($node->getRoot() === $rootCategory) {
            continue;
        }

        $page->removeFromCategory($node);
        $result['deleted'][] = $node->getId();
    }

    return $result;
}

/**
 * @throws InvalidInput
 *
 * @return string[]|true
 */
function updateKat(
    int $id,
    string $navn,
    int $vis,
    string $email,
    bool $customSortSubs,
    string $subsorder,
    int $parentId = null,
    int $iconId = null
) {
    $category = ORM::getOne(Category::class, $id);
    assert($category instanceof Category);
    if ($category->getParent() && null === $parentId) {
        throw new InvalidInput(_('You must select a parent category'));
    }

    if (null !== $parentId) {
        $parent = ORM::getOne(Category::class, $parentId);
        assert($parent instanceof Category);
        foreach ($parent->getBranch() as $node) {
            if ($node->getId() === $category->getId()) {
                throw new InvalidInput(_('The category can not be placed under itself.'));
            }
        }
        $category->setParent($parent);
    }

    //Set the order of the subs
    if ($customSortSubs) {
        updateKatOrder($subsorder);
    }

    $icon = null;
    if (null !== $iconId) {
        $icon = ORM::getOne(File::class, $iconId);
    }

    //Update kat
    $category->setRenderMode($vis)
        ->setEmail($email)
        ->setWeightedChildren($customSortSubs)
        ->setIcon($icon)
        ->setTitle($navn)
        ->save();

    return true;
}

function updateKatOrder(string $order): void
{
    foreach (explode(',', $order) as $weight => $id) {
        $category = ORM::getOne(Category::class, $id);
        assert($category instanceof Category);
        $category->setWeight($weight)->save();
    }
}

function updateSpecial(int $id, string $html, string $title = ''): bool
{
    $html = purifyHTML($html);

    $page = ORM::getOne(CustomPage::class, $id);
    assert($page instanceof CustomPage);
    if ($title) {
        $page->setTitle($title);
    }
    $page->setHtml($html)->save();

    if (1 === $id) {
        $category = ORM::getOne(Category::class, 0);
        assert($category instanceof Category);
        $category->setTitle($title)->save();
    }

    return true;
}

/**
 * Delete a page and all it's relations from the database.
 *
 * @return string[]
 */
function sletSide(int $pageId): array
{
    $page = ORM::getOne(Page::class, $pageId);
    if ($page) {
        $page->delete();
    }

    return ['class' => 'side' . $pageId];
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
    assert($invoice instanceof Invoice);
    sendInvoice($invoice);

    throw new InvalidInput(_('A Reminder was sent to the customer.'));
}

/**
 * @throws Exception
 *
 * @return string[]|true
 */
function pbsconfirm(int $id)
{
    /** @var ?Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    assert($invoice instanceof Invoice);

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
 *
 * @return string[]|true
 */
function annul(int $id)
{
    /** @var ?Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    assert($invoice instanceof Invoice);

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
