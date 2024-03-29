<?php

namespace App\Services;

use AJenbo\Imap;
use App\DTO\EmailConfig;
use App\Exceptions\Exception;
use App\Exceptions\SendEmail;
use App\Models\Contact;
use App\Models\Email;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    /** @var array<string, bool> */
    private array $ceche = [];

    /**
     * Checks if email an address looks valid and that an mx server is responding.
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

        return filter_var($user . '@' . $domain, FILTER_VALIDATE_EMAIL) && $this->checkMx($domain);
    }

    /**
     * Check that the domain has a valid MX setup.
     */
    private function checkMx(string $domain): bool
    {
        if (app()->environment('test')) {
            return true;
        }

        if (!isset($this->ceche[$domain])) {
            $dummy = [];
            $this->ceche[$domain] = getmxrr($domain, $dummy);
        }

        return $this->ceche[$domain];
    }

    /**
     * Send an email.
     *
     * @param Contact[] $bcc
     *
     * @throws SendEmail
     */
    public function send(Email $email, array $bcc = []): void
    {
        $emailConfigs = ConfigService::getEmailConfigs();
        $emailConfig = first($emailConfigs);
        if (isset($emailConfigs[$email->getSenderAddress()])) {
            $emailConfig = $emailConfigs[$email->getSenderAddress()];
        }

        $mailer = new PHPMailer(true);
        $mailer->setLanguage('dk');
        $mailer->CharSet = 'utf-8';

        $this->configureSmtp($mailer, $emailConfig);

        $mailer->setFrom($emailConfig->address, ConfigService::getString('site_name'));
        if ($email->getSenderAddress() !== $emailConfig->address) {
            $mailer->addReplyTo($email->getSenderAddress(), $email->getSenderName());
        }

        foreach ($bcc as $contact) {
            $mailer->addBCC($contact->getEmail(), $contact->getName());
        }

        $mailer->Subject = $email->getSubject();
        $mailer->msgHTML($email->getBody(), app()->basePath());
        $mailer->addAddress($email->getRecipientAddress(), $email->getRecipientName());

        if (!$mailer->send()) {
            throw new SendEmail(_('Failed to send email.'));
        }

        $this->uploadEmail($emailConfig, $mailer->getSentMIMEMessage());
    }

    /**
     * Set up the SMTP configuration.
     */
    private function configureSmtp(PHPMailer $mailer, EmailConfig $emailConfig): void
    {
        $mailer->isSMTP();
        $mailer->Host = $emailConfig->smtpHost;
        $mailer->Port = $emailConfig->smtpPort;
        if ($emailConfig->smtpAuth) {
            $mailer->SMTPAuth = true;
            $mailer->Username = $emailConfig->address;
            $mailer->Password = $emailConfig->password;
        }
    }

    /**
     * Upload email to the sendt box of the imap account.
     */
    private function uploadEmail(EmailConfig $emailConfig, string $mimeMessage): void
    {
        if (!$emailConfig->imapHost) {
            return;
        }

        $imap = new Imap(
            $emailConfig->address,
            $emailConfig->password,
            $emailConfig->imapHost,
            $emailConfig->imapPort
        );
        $imap->append($emailConfig->sentBox, $mimeMessage, '\Seen');
    }
}
