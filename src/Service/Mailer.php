<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class Mailer extends AbstractController
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendMail(array $emailData): void
    {
        $email = (new Email())
            ->from($emailData['from'])
            ->to($emailData['to'])
            ->subject($emailData['subject'])
            ->html($emailData['text']);

        $this->mailer->send($email);
    }
}