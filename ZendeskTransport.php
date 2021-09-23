<?php
declare(strict_types=1);

namespace Siru\Notifier\Bridge\Zendesk;

use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZendeskTransport extends AbstractTransport
{

    protected const HOST = 'zendesk.com';

    private $username;

    private $token;

    public function __construct(string $subdomain, string $username, string $token, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->username = $username;
        $this->token = $token;
        parent::__construct($client, $dispatcher);
        $this->setHost($subdomain . '.' . self::HOST);
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof ChatMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, ChatMessage::class, $message);
        }

        if ($message->getOptions() && !$message->getOptions() instanceof ZendeskOptions) {
            throw new LogicException(sprintf('The "%s" transport only supports instances of "%s" for options.', __CLASS__, ZendeskOptions::class));
        }
        $opts = $message->getOptions();
        if (!$opts) {
            if ($notification = $message->getNotification()) {
                $opts = ZendeskOptions::fromNotification($notification);
            } else {
                $opts = ZendeskOptions::fromMessage($message);
            }
        }

        if (true === $opts->isRequest()) {
            $endPoint = '/api/v2/requests.json';
        } else {
            $endPoint = '/api/v2/tickets.json';
        }
        $url = sprintf('https://%s/%s', $this->getEndpoint(), $endPoint);
        $username = $opts->getEmailAddress() ?: $this->username;

        $fields = $opts->toArray();
        $response = $this->client->request('POST', $url, [
            'auth_basic' => [$username . '/token', $this->token],
            'json' => array_filter($fields),
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote Zendesk server.', $response, 0, $e);
        }

        try {
            $result = $response->toArray(false);
        } catch (JsonException $jsonException) {
            throw new TransportException('Unable to create Zendesk request: Invalid response.', $response, $statusCode, $jsonException);
        }

        if (201 !== $statusCode) {
            throw new TransportException(sprintf('Unable to create Zendesk request: "%s".', $result['description'] ?? $response->getContent(false)), $response, $statusCode);
        }

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId((string) $result['request']['id']);

        return $sentMessage;
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage && (null === $message->getOptions() || $message->getOptions() instanceof ZendeskOptions);
    }

    public function __toString(): string
    {
        return sprintf('zendesk://%s:%s@%s',
            $this->username,
            $this->token,
            $this->getEndpoint()
        );
    }
}