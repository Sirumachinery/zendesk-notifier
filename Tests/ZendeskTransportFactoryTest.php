<?php
declare(strict_types=1);

namespace Siru\Notifier\Bridge\Zendesk\Tests;

use Siru\Notifier\Bridge\Zendesk\ZendeskTransportFactory;
use Symfony\Component\Notifier\Test\TransportFactoryTestCase;
use Symfony\Component\Notifier\Transport\TransportFactoryInterface;

final class ZendeskTransportFactoryTest extends TransportFactoryTestCase
{
    /**
     * @return ZendeskTransportFactory
     */
    public function createFactory(): TransportFactoryInterface
    {
        return new ZendeskTransportFactory();
    }

    public static function createProvider(): iterable
    {
        yield [
            'zendesk://johndoe:abcde@foo.zendesk.com',
            'zendesk://johndoe:abcde@foo',
        ];
        yield [
            'zendesk://johndoe:abcde@foo.zendesk.com',
            'zendesk://johndoe:abcde@foo/foo?xoo=xer',
        ];
    }

    public static function supportsProvider(): iterable
    {
        yield [true, 'zendesk://host/path'];
        yield [false, 'foobar://host/path'];
    }

    public static function incompleteDsnProvider(): iterable
    {
        yield 'missing token' => ['zendesk://username@foo'];
    }

    public static function unsupportedSchemeProvider(): iterable
    {
        yield ['foobar://host/path'];
    }
}
