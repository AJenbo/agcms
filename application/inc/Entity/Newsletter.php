<?php namespace AGCMS\Entity;

use AGCMS\Exception\Exception;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Service\EmailService;
use Throwable;

class Newsletter extends AbstractEntity implements InterfaceRichText
{
    /**  Table name in database. */
    const TABLE_NAME = 'newsmails';

    // Backed by DB

    /** @var string Sender email address */
    private $from = '';

    /** @var string Email subject */
    private $subject = '';

    /** @var string Body */
    private $html = '';

    /** @var bool Has it been sent. */
    private $sent = false;

    /** @var string[] List of topics is covered. */
    private $interests = [];

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data = [])
    {
        $this->setFrom($data['from'] ?? '')
            ->setSubject($data['subject'] ?? '')
            ->setInterests($data['interests'] ?? [])
            ->setHtml($data['html'] ?? '')
            ->setSent($data['sent'] ?? false)
            ->setId($data['id'] ?? null);
    }

    /**
     * @param string $from
     *
     * @return $this
     */
    public function setFrom(string $from): self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $html
     *
     * @return $this
     */
    public function setHtml(string $html): InterfaceRichText
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * @param bool $sent
     *
     * @return $this
     */
    public function setSent(bool $sent): self
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Set newsletter interests.
     *
     * @param string[] $interests
     *
     * @return $this
     */
    public function setInterests(array $interests): self
    {
        $this->interests = $interests;

        return $this;
    }

    /**
     * Get interests.
     *
     * @return string[]
     */
    public function getInterests(): array
    {
        return $this->interests;
    }

    /**
     * Map data from DB table to entity.
     *
     * @param array $data The data from the database
     *
     * @return array
     */
    public static function mapFromDB(array $data): array
    {
        $interests = explode('<', $data['interests']);
        $interests = array_map('html_entity_decode', $interests);

        return [
            'id'         => $data['id'],
            'from'       => $data['from'],
            'subject'    => $data['subject'],
            'html'       => $data['text'],
            'sent'       => (bool) $data['sendt'],
            'interests'  => $interests,
        ];
    }

    // ORM related functions

    /**
     * Get data in array format for the database.
     *
     * @return string[]
     */
    public function getDbArray(): array
    {
        $interests = array_map('htmlspecialchars', $this->interests);
        $interests = implode('<', $interests);

        return [
            'from'         => app('db')->quote($this->from),
            'subject'      => app('db')->quote($this->subject),
            'text'         => app('db')->quote($this->html),
            'sendt'        => app('db')->quote((string) (int) $this->sent),
            'interests'    => app('db')->quote($interests),
        ];
    }

    /**
     * Count number of recipients for this newsletter.
     *
     * @return int
     */
    public function countRecipients(): int
    {
        app('db')->addLoadedTable('email');
        $emails = app('db')->fetchOne(
            "
            SELECT count(DISTINCT email) as 'count'
            FROM `email`
            WHERE `email` NOT LIKE '' AND `kartotek` = '1'
            " . $this->getContactFilterSQL()
        );

        return $emails['count'];
    }

    /**
     * Get SQL for filtering contacts based on interests.
     *
     * @return string
     */
    private function getContactFilterSQL(): string
    {
        $andWhere = '';
        if ($this->interests) {
            foreach ($this->interests as $interest) {
                if ($andWhere) {
                    $andWhere .= ' OR ';
                }
                $andWhere .= '`interests` LIKE \'';
                $andWhere .= $interest;
                $andWhere .= '\' OR `interests` LIKE \'';
                $andWhere .= $interest;
                $andWhere .= '<%\' OR `interests` LIKE \'%<';
                $andWhere .= $interest;
                $andWhere .= '\' OR `interests` LIKE \'%<';
                $andWhere .= $interest;
                $andWhere .= '<%\'';
            }
            $andWhere = ' AND (' . $andWhere . ')';
        }

        return $andWhere;
    }

    /**
     * Send the newsletter.
     *
     * @todo resend failed emails, save bcc
     *
     * @throws Exception
     *
     * @return void
     */
    public function send(): void
    {
        if ($this->sent) {
            throw new Exception(_('The newsletter has already been sent.'));
        }

        $andWhere = $this->getContactFilterSQL();
        /** @var Contact[] */
        $contacts = app('orm')->getByQuery(
            Contact::class,
            'SELECT * FROM email WHERE email NOT LIKE \'\' AND `kartotek` = \'1\' ' . $andWhere . ' GROUP BY `email`'
        );

        // Split in to groups of 99 to avoid server limit on bcc
        $contactsGroups = [];
        foreach ($contacts as $x => $contact) {
            $contactsGroups[(int) floor($x / 99) + 1][] = $contact;
        }

        $data = [
            'siteName' => config('site_name'),
            'css'      => file_get_contents(
                app()->basePath('/theme/' . config('theme', 'default') . '/style/email.css')
            ),
            'body'     => str_replace(' href="/', ' href="' . config('base_url') . '/', $this->html),
        ];
        $emailService = new EmailService();
        $failedCount = 0;
        foreach ($contactsGroups as $bcc) {
            $email = new Email([
                'subject'          => $this->subject,
                'body'             => app('render')->render('email/newsletter', $data),
                'senderName'       => config('site_name'),
                'senderAddress'    => $this->from,
                'recipientName'    => config('site_name'),
                'recipientAddress' => $this->from,
            ]);

            try {
                $emailService->send($email, $bcc);
            } catch (Throwable $exception) {
                app()->logException($exception);
                $failedCount += count($bcc);
            }
        }
        if ($failedCount) {
            throw new Exception(sprintf(_('Email %d/%d failed to be sent.'), $failedCount, count($contacts)));
        }

        $this->sent = true;
        $this->save();
    }
}
