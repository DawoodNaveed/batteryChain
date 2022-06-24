<?php

namespace App\Service;

use Mailjet\Client;
use Mailjet\Resources;
use Mailjet\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Class EmailService
 * @package AppBundle\Service
 * @property $mailjetKey
 * @property $mailjetSecret
 * @property $mailjetSenderName
 * @property $mailjetFromEmail
 * @property $twig
 * @property TranslatorInterface translator
 */
class EmailService
{
    /**
     * EmailService constructor.
     * @param Environment $twig
     * @param string $mailjetKey
     * @param string $mailjetSecret
     * @param string $mailjetFromName
     * @param string $mailjetFromEmail
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Environment $twig,
        string $mailjetKey,
        string $mailjetSecret,
        string $mailjetFromName,
        string $mailjetFromEmail,
        TranslatorInterface $translator
    ) {
        $this->twig = $twig;
        $this->mailjetKey = $mailjetKey;
        $this->mailjetSecret = $mailjetSecret;
        $this->mailjetSenderName = $mailjetFromName;
        $this->mailjetFromEmail = $mailjetFromEmail;
        $this->translator = $translator;
    }

    /**
     * @param array $data
     * @param array|null $attachment
     * @param string|null $fromEmail
     * @param string|null $fromName
     * @param string|null $replyToEmail
     * @return Response
     */
    public function sendEmail(
        array $data,
        ?array $attachment = null,
        ?string $fromEmail = null,
        ?string $fromName = null,
        ?string $replyToEmail = null
    ): Response {
        if (is_array($data['email'])) {
            $to = [];
            foreach ($data['email'] as $email) {
                $to[] = [
                    'Email' => $email
                ];
            }
        } else {
            $to[] = [
                'Email' => $data['email']
            ];
        }

        $fromEmail = is_null($fromEmail) ? $this->mailjetFromEmail : $fromEmail;
        $fromName = is_null($fromName) ? $this->mailjetSenderName : $fromName;

        $client = new Client($this->mailjetKey, $this->mailjetSecret);

        $body = [
            'Recipients' => $to,
            'FromEmail' => $fromEmail,
            'FromName' => $fromName,
            'Subject' => $data['subject'],
            'Html-part' => $this->twig->render(
                $data['template_name'],
                [
                    'body' => $data['body']
                ]
            )
        ];
        dd($body['Html-part']);

        if (!is_null($replyToEmail)) {
            $body['Headers'] = [
                'Reply-To' => $replyToEmail
            ];
        }

        if ($attachment !== null) {
            $body["Attachments"] = $attachment;
        }

        return $client->post(Resources::$Email, ['body' => $body]);
    }
}
