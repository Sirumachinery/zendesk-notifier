<?php
declare(strict_types=1);

namespace Siru\Notifier\Bridge\Zendesk\Tests;

use PHPUnit\Framework\TestCase;
use Siru\Notifier\Bridge\Zendesk\ZendeskOptions;

class ZendeskOptionsTest extends TestCase
{

    public function testCreatesOptionsArray() : void
    {
        $options = (new ZendeskOptions())
            ->subject('foo')
            ->text('bar')
            ->priority('urgent')
            ->tag('xooxer');

        $expected = [
            'subject' => 'foo',
            'comment' => [
                'body' => 'bar'
            ],
            'priority' => 'urgent',
            'tags' => ['xooxer']
        ];

        $this->assertEquals($expected, $options->toArray());
    }

}
