<?php

namespace InnoGE\LaravelMsGraphMail;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use InnoGE\LaravelMsGraphMail\Services\MicrosoftGraphApiService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{

    private $emailQueue;

    public function __construct(protected MicrosoftGraphApiService $microsoftGraphApiService, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        parent::__construct($dispatcher, $logger);
    }

    public function __toString(): string
    {
        return 'microsoft+graph+api://';
    }

    public function setTenantId(string $tenantId): self
    {
        $this->microsoftGraphApiService->tenantId = $tenantId;

        return $this;
    }

    public function setEmailQueue($emailQueue): self
    {
        $this->emailQueue = $emailQueue;

        return $this;
    }


    /**
     * @throws RequestException
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $html = $email->getHtmlBody();

        [$attachments, $html] = $this->prepareAttachments($email, $html);

        $payload = [
            'subject' => $email->getSubject(),
            'body' => [
                'contentType' => $html === null ? 'Text' : 'HTML',
                'content' => $html ?: $email->getTextBody(),
            ],
            'toRecipients' => $this->transformEmailAddresses($this->getRecipients($email, $envelope)),
            'ccRecipients' => $this->transformEmailAddresses(collect($email->getCc())),
            'bccRecipients' => $this->transformEmailAddresses(collect($email->getBcc())),
            'replyTo' => $this->transformEmailAddresses(collect($email->getReplyTo())),
            'sender' => $this->transformEmailAddress($envelope->getSender()),
            'attachments' => $attachments,
        ];

        $draftMessage = $this->microsoftGraphApiService->draft($this->getFromAddress($email->getFrom()), $payload);

        $decodedMessage = json_decode($draftMessage->getBody()->getContents());

        $messageId = $decodedMessage->id;

        $this->emailQueue?->update(['immutable_message_id' => $decodedMessage->internetMessageId]);
    
        $this->microsoftGraphApiService->send($this->getFromAddress($email->getFrom()),$messageId);
      
    }

    protected function getFromAddress($fromArray){

        $from = $fromArray[0];
        return $from->getAddress();
    }

    /**
     * @param  Collection<Address>  $recipients
     * @return array
     */
    protected function transformEmailAddresses(Collection $recipients): array
    {
        return $recipients
            ->map(fn (Address $recipient) => $this->transformEmailAddress($recipient))
            ->toArray();
    }

    /**
     * @param  Address  $address
     * @return array
     */
    protected function transformEmailAddress(Address $address): array
    {
        return [
            'emailAddress' => [
                'address' => $address->getAddress(),
            ],
        ];
    }

    /**
     * @param  Email  $email
     * @param  Envelope  $envelope
     * @return Collection<Address>
     */
    protected function getRecipients(Email $email, Envelope $envelope): Collection
    {
        return collect($envelope->getRecipients())
            ->filter(fn (Address $address) => !in_array($address, array_merge($email->getCc(), $email->getBcc()), true));
    }

    /**
     * @param Email $email
     * @param string|null $html
     * @return array
     */
    protected function prepareAttachments(Email $email, ?string $html): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $fileName = $headers->getHeaderParameter('Content-Disposition', 'filename');

            $attachments[] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $fileName,
                'contentType' => $attachment->getMediaType(),
                'contentBytes' => base64_encode($attachment->getBody()),
                'contentId' => $fileName,
                'isInline' => $headers->getHeaderBody('Content-Disposition') === 'inline',
            ];
        }

        return [$attachments, $html];
    }
}
