<?php namespace App\Services;

use AJenbo\Imap;
use App\Application;
use App\Exceptions\Exception;
use App\Exceptions\SendEmail;
use App\Models\Contact;
use App\Models\Email;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    /** @var array<string, bool> */
    private $ceche = [];

    /**
     * Checks if email an address looks valid and that an mx server is responding.
     *
     * @param string $email The email address to check
     *
     * @return bool
     */
    public function valideMail(string $email): bool
    {
        $user = preg_replace('/@.+$/u', '', $email);
        if (null === $user) {
            throw new Exception('preg_replace failed');
        }
        $domain = preg_replace('/^.+?@/u', '', $email);
        if (null === $domain) {
            throw new Exception('preg_replace failed');
        }
        $domain = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
        if (!$domain) {
            return false;
        }

        if (filter_var($user . '@' . $domain, FILTER_VALIDATE_EMAIL) && $this->checkMx($domain)) {
            return true;
        }

        return false;
    }

    /**
     * Check that the domain has a valid MX setup.
     *
     * @param string $domain
     *
     * @return bool
     */
    private function checkMx(string $domain): bool
    {
        if (!isset($this->ceche[$domain])) {
            $dummy = [];
            $this->ceche[$domain] = true;
            /** @var Application */
            $app = app();
            if (!$app->environment('test')) {
                $this->ceche[$domain] = getmxrr($domain, $dummy);
            }
        }

        return $this->ceche[$domain];
    }

    /**
     * Send an email.
     *
     * @param Email     $email
     * @param Contact[] $bcc
     *
     * @throws SendEmail
     *
     * @return void
     */
    public function send(Email $email, array $bcc = []): void
    {
        $emailConfig = first(config('emails'));
        if (isset(config('emails')[$email->getSenderAddress()])) {
            $emailConfig = config('emails')[$email->getSenderAddress()];
        }

        $mailer = new PHPMailer(true);
        $mailer->setLanguage('dk');
        $mailer->CharSet = 'utf-8';

        $this->configureSmtp($mailer, $emailConfig);

        $mailer->setFrom($emailConfig['address'], config('site_name'));
        if ($email->getSenderAddress() !== $emailConfig['address']) {
            $mailer->addReplyTo($email->getSenderAddress(), $email->getSenderName());
        }

        foreach ($bcc as $contact) {
            $mailer->addBCC($contact->getEmail(), $contact->getName());
        }

        /** @var Application */
        $app = app();

        $mailer->Subject = $email->getSubject();
        $mailer->msgHTML($email->getBody(), $app->basePath());
        $mailer->addAddress($email->getRecipientAddress(), $email->getRecipientName());

        if (!$mailer->send()) {
            throw new SendEmail(_('Failed to send email.'));
        }

        $this->uploadEmail($emailConfig, $mailer->getSentMIMEMessage());
    }

    /**
     * Set up the SMTP configuration.
     *
     * @param PHPMailer            $mailer
     * @param array<string, mixed> $emailConfig
     *
     * @return void
     */
    private function configureSmtp(PHPMailer $mailer, array $emailConfig): void
    {
        $mailer->isSMTP();
        $mailer->Host = $emailConfig['smtpHost'];
        $mailer->Port = $emailConfig['smtpPort'];
        if ($emailConfig['smtpAuth']) {
            $mailer->SMTPAuth = true;
            $mailer->Username = $emailConfig['address'];
            $mailer->Password = $emailConfig['password'];
        }
    }

    /**
     * Upload email to the sendt box of the imap account.
     *
     * @param array<string, mixed> $emailConfig
     * @param string               $mimeMessage
     *
     * @return void
     */
    private function uploadEmail(array $emailConfig, string $mimeMessage): void
    {
        if (!$emailConfig['imapHost']) {
            return;
        }

        $imap = new Imap(
            $emailConfig['address'],
            $emailConfig['password'],
            $emailConfig['imapHost'],
            $emailConfig['imapPort']
        );
        $imap->append($emailConfig['sentBox'], $mimeMessage, '\Seen');
    }
}
