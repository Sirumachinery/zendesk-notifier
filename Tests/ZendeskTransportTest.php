<?php
declare(strict_types=1);

namespace Siru\Notifier\Bridge\Zendesk\Tests;

use Siru\Notifier\Bridge\Zendesk\ZendeskOptions;
use Siru\Notifier\Bridge\Zendesk\ZendeskTransport;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Test\TransportTestCase;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ZendeskTransportTest extends TransportTestCase
{
    /**
     * @return ZendeskTransport
     */
    public function createTransport(HttpClientInterface $client = null, string $threadKey = null): TransportInterface
    {
        return new ZendeskTransport('subdomain', 'foo@local.host', 'abc123', $client ?? $this->createMock(HttpClientInterface::class));
    }

    public function toStringProvider(): iterable
    {
        yield ['zendesk://foo@local.host:abc123@subdomain.zendesk.com', $this->createTransport()];
    }

    public function supportedMessagesProvider(): iterable
    {
        yield [new ChatMessage('My message')];
    }

    public function unsupportedMessagesProvider(): iterable
    {
        yield [new SmsMessage('1234567', 'My message')];
        yield [$this->createMock(MessageInterface::class)];
    }

    public function testInvalidResponseThrowsTransportException() : void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to create Zendesk request: Invalid response.');
        $this->expectExceptionCode(500);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(500);
        $response->expects($this->once())
            ->method('getContent')
            ->with(false)
            ->willReturn('foo');
        $client = new MockHttpClient(function () use ($response): ResponseInterface {
            return $response;
        });

        $transport = $this->createTransport($client);

        $transport->send(new ChatMessage('My message'));
    }

    public function testHttpErrorThrowsTransportException() : void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to create Zendesk request: "foo"');
        $this->expectExceptionCode(500);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(500);
        $response->expects($this->once())
            ->method('getContent')
            ->with(false)
            ->willReturn('{"description":"foo"}');
        $client = new MockHttpClient(function () use ($response): ResponseInterface {
            return $response;
        });

        $transport = $this->createTransport($client);

        $transport->send(new ChatMessage('My message'));
    }

    public function testCreatesTicketWithChatMessage() : void
    {
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(201);

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn('{"request":{"description":"","id":33,"status":"new","subject":"My message"}}');

        $chatMessage = new ChatMessage('My message');

        $expectedBody = json_encode([
            'ticket' => [
                'subject' => 'My message'
            ]
        ]);

        $client = new MockHttpClient(function (string $method, string $url, array $options = []) use ($response, $expectedBody): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertStringEndsWith('/api/v2/tickets.json', $url);
            $this->assertSame($expectedBody, $options['body']);

            return $response;
        });

        $transport = $this->createTransport($client);

        $sentMessage = $transport->send($chatMessage);

        $this->assertSame('33', $sentMessage->getMessageId());
    }

    public function testCreatesTicketWithNotification() : void
    {
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(201);

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn('{"request":{"description":"","id":33,"status":"new","subject":"My message"}}');

        $notification = new Notification('My message');
        $chatMessage = ChatMessage::fromNotification($notification);

        $expectedBody = json_encode([
            'ticket' => [
                'subject' => 'My message',
                'priority' => 'high'
            ]
        ]);

        $client = new MockHttpClient(function (string $method, string $url, array $options = []) use ($response, $expectedBody): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertStringEndsWith('/api/v2/tickets.json', $url);
            $this->assertSame($expectedBody, $options['body']);

            return $response;
        });

        $transport = $this->createTransport($client);

        $sentMessage = $transport->send($chatMessage);

        $this->assertSame('33', $sentMessage->getMessageId());
    }

    public function testCreatesTicketWithOptions() : void
    {
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(201);

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn('{"request":{"description":"","id":33,"status":"new","subject":"My message"}}');

        $options = (new ZendeskOptions())
            ->subject('My message')
            ->text('My description')
            ->priority('low');

        $chatMessage = new ChatMessage('');
        $chatMessage->options($options);

        $expectedBody = json_encode([
            'ticket' => [
                'subject' => 'My message',
                'comment' => [
                    'body' => 'My description'
                ],
                'priority' => 'low'
            ]
        ]);

        $client = new MockHttpClient(function (string $method, string $url, array $options = []) use ($response, $expectedBody): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertStringEndsWith('/api/v2/tickets.json', $url);
            $this->assertSame($expectedBody, $options['body']);

            return $response;
        });

        $transport = $this->createTransport($client);

        $sentMessage = $transport->send($chatMessage);

        $this->assertSame('33', $sentMessage->getMessageId());
    }

    public function testCreatesRequestForUserWithOptions() : void
    {
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(201);

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn('{"request":{"description":"","id":33,"status":"new","subject":"My message"}}');

        $options = (new ZendeskOptions())
            ->subject('My message')
            ->asRequest()
            ->emailAddress('bar@local.host')
            ->requester('foo@local.host');

        $chatMessage = new ChatMessage('');
        $chatMessage->options($options);

        $expectedBody = json_encode([
            'request' => [
                'subject' => 'My message',
                'requester' => [
                    'name' => 'foo',
                    'email' => 'foo@local.host'
                ]
            ],
        ]);

        $client = new MockHttpClient(function (string $method, string $url, array $options = []) use ($response, $expectedBody): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertStringEndsWith('/api/v2/requests.json', $url);
            $this->assertSame($expectedBody, $options['body']);

            return $response;
        });

        $transport = $this->createTransport($client);

        $sentMessage = $transport->send($chatMessage);

        $this->assertSame('33', $sentMessage->getMessageId());
    }

}