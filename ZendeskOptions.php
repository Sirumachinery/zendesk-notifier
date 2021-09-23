<?php
declare(strict_types=1);

namespace Siru\Notifier\Bridge\Zendesk;

use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;
use Symfony\Component\Notifier\Notification\Notification;

final class ZendeskOptions implements MessageOptionsInterface
{

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var bool
     */
    private $asRequest = false;

    /**
     * @var string|null
     */
    private $emailAddress = null;

    public static function fromNotification(Notification $notification): self
    {
        $options = new self();

        $options->subject($notification->getSubject());

        $text = '';
        if ($notification->getContent()) {
            $text .= $notification->getContent();
        }

        if ($exception = $notification->getExceptionAsString()) {
            $text .= "\r\n".'```' . $exception . '```';
        }

        switch($notification->getImportance()) {
            case Notification::IMPORTANCE_LOW:
            case Notification::IMPORTANCE_HIGH:
            case Notification::IMPORTANCE_URGENT:
                $options->priority($notification->getImportance());
                break;
            case Notification::IMPORTANCE_MEDIUM:
                $options->priority('normal');
                break;
        }

        if (false === empty($text)) {
            $options->text($text);
        }

        return $options;
    }

    public static function fromMessage(ChatMessage $message): self
    {
        $options = new self();

        $options->subject($message->getSubject());

        return $options;
    }

    public function toArray(): array
    {
        return $this->options;
    }

    public function subject(string $text): self
    {
        $this->options['subject'] = $text;

        return $this;
    }

    public function text(string $text): self
    {
        $this->options['comment']['body'] = $text;

        return $this;
    }

    public function priority(string $priority): self
    {
        $this->options['priority'] = $priority;

        return $this;
    }

    public function tag(string $tag): self
    {
        $this->options['tags'][] = $tag;

        return $this;
    }

    public function asRequest(bool $asRequest = true): self
    {
        $this->asRequest = $asRequest;

        return $this;
    }

    public function emailAddress(string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    public function requester(string $emailAddress, ?string $name = null): self
    {
        if (null === $name) {
            $name = explode('@', $emailAddress, 2)[0];
        }
        $this->options['requester'] = [
            'name' => $name,
            'email' => $emailAddress
        ];
        return $this;
    }

    public function isRequest() : bool
    {
        return $this->asRequest;
    }

    public function getEmailAddress() : ?string
    {
        return $this->emailAddress;
    }

    public function getRecipientId(): ?string
    {
        return null;
    }
}