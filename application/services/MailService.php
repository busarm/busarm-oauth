<?php

namespace Application\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use System\Interfaces\SingletonInterface;
use System\Traits\Singleton;

class MailService implements SingletonInterface
{
    use Singleton;

    /**
     * @var PHPMailer
     */
    private $mailer;

    /**
     * @param string $protocol [mail, smtp, sendmail]
     * @param integer $timeout
     * @param string $charset [us-ascii, iso-8859-1, utf-8]
     */
    public function __construct(private $protocol = 'smtp', private $timeout = 10, private $charset = 'utf-8')
    {
        $this->mailer = new PHPMailer(true);

        // Server settings
        if ($this->protocol == "mail") {
            $this->mailer->isMail();
        } else if ($this->protocol == "smtp") {
            $this->mailer->isSMTP();
        } else if ($this->protocol == "sendmail") {
            $this->mailer->isSendmail();
        }

        $this->mailer->Username = SMTP_KEY;
        $this->mailer->Password = SMTP_SECRET;
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->Port = SMTP_PORT;
        $this->mailer->SMTPAuth = true;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Timeout = $this->timeout;
        $this->mailer->CharSet = $this->charset;
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
            $this->mailer->setFrom($from, COMPANY_NAME ?? APP_NAME);
            $this->mailer->addReplyTo($from, COMPANY_NAME ?? APP_NAME);
            $this->mailer->addAddress($to);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;

            return $this->mailer->send();
        } catch (Exception $e) {
            app()->reporter->reportException($e); // Report
        }

        return false;
    }
}
