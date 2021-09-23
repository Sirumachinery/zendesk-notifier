<?php
declare(strict_types=1);

namespace Siru\Notifier\Bridge\Zendesk;

use Symfony\Component\Notifier\Exception\IncompleteDsnException;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

final class ZendeskTransportFactory extends AbstractTransportFactory
{
    /**
     * @param Dsn $dsn Format: zendesk://<username>:<token>@<subdomain>
     *
     * @return ZendeskTransport
     */
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();

        if ('zendesk' !== $scheme) {
            throw new UnsupportedSchemeException($dsn, 'zendesk', $this->getSupportedSchemes());
        }

        $username = $this->getUser($dsn);
        $token = $this->getPassword($dsn);
        $domain = $dsn->getHost();

        if (true === empty($token)) {
            throw new IncompleteDsnException('Missing API token in Zendesk DSN.');
        }

        return new ZendeskTransport($domain, $username, $token, $this->client, $this->dispatcher);
    }

    protected function getSupportedSchemes(): array
    {
        return ['zendesk'];
    }
}