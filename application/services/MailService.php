<?php

namespace Application\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    /**
     * @var PHPMailer
     */
    private $mail;

    /**
     * @param string $mail_protocol [mail, smtp, sendmail]
     * @param integer $mail_timeout
     * @param string $mail_charset
     */
    public function __construct(private $mail_protocol = 'smtp', private $mail_timeout = 10, private $mail_charset = 'utf-8')
    {
        $this->mail = new PHPMailer(true);

        // Server settings
        if ($this->mail_protocol == "mail") {
            $this->mail->isMail();
        } else if ($this->mail_protocol == "smtp") {
            $this->mail->isSMTP();
        } else if ($this->mail_protocol == "sendmail") {
            $this->mail->isSendmail();
        }

        $this->mail->Username = SMTP_KEY;
        $this->mail->Password = SMTP_SECRET;
        $this->mail->Host = SMTP_HOST;
        $this->mail->Port = SMTP_PORT;
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = 'tls';
        $this->mail->Timeout = $this->mail_timeout;
        $this->mail->CharSet = $this->mail_charset;
    }

    /**
     * Send Mail to an intended client
     * @param string $subject
     * @param string $message
     * @param string $to
     * @param string $from
     * @return bool
     */
    public function send(
        $subject,
        $message,
        $to,
        $from = null
    ) {

        try {

            // Recipients
            $from = !empty($from) ? $from : EMAIL_INFO;
            $this->mail->setFrom($from, COMPANY_NAME ?? APP_NAME);
            $this->mail->addReplyTo($from, COMPANY_NAME ?? APP_NAME);
            $this->mail->addAddress($to);

            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $message;

            return $this->mail->send();
        } catch (Exception $e) {
            app()->reportException($e); // Report
        }

        return false;
    }
}
