# Zendesk Notifier

Provides Zendesk integration for Symfony Notifier.

## Requirements

- PHP 7.3
- Symfony Notifier 5.3+ and HttpClient components
- Zendesk subdomain, username and API token

## Installation

```shell script
$ composer require sirumobile/zendesk-notifier
```

Add correct DSN with your Zendesk credentials to ENV. Then configure notifier and
add ZendeskTransportFactory to your services.

```dotenv
# .env
ZENDESK_DSN=zendesk://USERNAME:TOKEN@SUBDOMAIN
```

You can get your API key from Zendesk Admin panel. If your Zendesk domain is yourcompany.zendesk.com and username
support@yourcompany.com, the DSN would look something like this:

```dotenv
# .env
ZENDESK_DSN=zendesk://support@yourcompany.com:abc123@yourcompany
```

```yaml
# ./config/packages/notifier.yaml
framework:
    notifier:
        texter_transports:
            zendesk: '%env(ZENDESK_DSN)%'
```

```yaml
# ./config/services.yaml
Siru\Notifier\Bridge\Zendesk\ZendeskTransportFactory:
    tags: [ texter.transport_factory ]
```

## Usage

By default, transport creates a Ticket on behalf of agent or admin. To create a Request on behalf of user,
set the correct options using ZendOptions class.

```php
$options = (new ZendeskOptions())
    ->subject('My message')
    ->asRequest()
    ->emailAddress('some-user@domain');
$chatMessage = new ChatMessage('');
$chatMessage->options($options);
```