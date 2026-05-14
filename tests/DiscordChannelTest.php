<?php

namespace NomanHameed\DiscordNotify\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Notification;
use NomanHameed\DiscordNotify\Channels\DiscordChannel;

class DiscordChannelTest extends TestCase
{
    /** @test */
    public function it_sends_a_single_post_when_content_is_within_limit()
    {
        $container = [];
        $channel = $this->makeChannel([new Response(204)], $container);

        $channel->send(null, $this->notificationStub(['content' => 'hello world']));

        $this->assertCount(1, $container);
        $body = json_decode((string) $container[0]['request']->getBody(), true);
        $this->assertSame('hello world', $body['content']);
    }

    /** @test */
    public function it_splits_oversize_content_into_multiple_posts()
    {
        $container = [];
        $channel = $this->makeChannel([
            new Response(204),
            new Response(204),
            new Response(204),
        ], $container);

        $longContent = str_repeat('a', DiscordChannel::MAX_CONTENT_LENGTH * 2 + 100);

        $channel->send(null, $this->notificationStub(['content' => $longContent]));

        $this->assertCount(3, $container);
        foreach ($container as $transaction) {
            $body = json_decode((string) $transaction['request']->getBody(), true);
            $this->assertLessThanOrEqual(DiscordChannel::MAX_CONTENT_LENGTH, mb_strlen($body['content']));
        }
    }

    /** @test */
    public function it_only_attaches_embeds_to_the_first_chunk_when_splitting()
    {
        $container = [];
        $channel = $this->makeChannel([new Response(204), new Response(204)], $container);

        $longContent = str_repeat('b', DiscordChannel::MAX_CONTENT_LENGTH + 50);

        $channel->send(null, $this->notificationStub([
            'content' => $longContent,
            'embeds' => [['title' => 'Alert', 'description' => 'Something happened']],
        ]));

        $this->assertCount(2, $container);
        $first = json_decode((string) $container[0]['request']->getBody(), true);
        $second = json_decode((string) $container[1]['request']->getBody(), true);

        $this->assertArrayHasKey('embeds', $first);
        $this->assertSame('Alert', $first['embeds'][0]['title']);
        $this->assertArrayNotHasKey('embeds', $second);
    }

    /** @test */
    public function it_truncates_the_final_chunk_when_exceeding_the_chunk_cap()
    {
        $container = [];
        $maxChunks = DiscordChannel::MAX_CONTENT_CHUNKS;
        $responses = array_fill(0, $maxChunks, new Response(204));
        $channel = $this->makeChannel($responses, $container);

        $longContent = str_repeat('c', DiscordChannel::MAX_CONTENT_LENGTH * ($maxChunks + 2));

        $channel->send(null, $this->notificationStub(['content' => $longContent]));

        $this->assertCount($maxChunks, $container);
        $lastBody = json_decode((string) end($container)['request']->getBody(), true);
        $this->assertStringEndsWith(DiscordChannel::TRUNCATION_SUFFIX, $lastBody['content']);
    }

    /** @test */
    public function it_caps_oversize_embed_fields()
    {
        $container = [];
        $channel = $this->makeChannel([new Response(204)], $container);

        $channel->send(null, $this->notificationStub([
            'content' => 'short',
            'embeds' => [[
                'title' => str_repeat('t', DiscordChannel::MAX_EMBED_TITLE + 50),
                'description' => str_repeat('d', DiscordChannel::MAX_EMBED_DESCRIPTION + 100),
                'fields' => [[
                    'name' => 'ok',
                    'value' => str_repeat('v', DiscordChannel::MAX_EMBED_FIELD_VALUE + 200),
                ]],
            ]],
        ]));

        $body = json_decode((string) $container[0]['request']->getBody(), true);
        $embed = $body['embeds'][0];

        $this->assertLessThanOrEqual(DiscordChannel::MAX_EMBED_TITLE, mb_strlen($embed['title']));
        $this->assertLessThanOrEqual(DiscordChannel::MAX_EMBED_DESCRIPTION, mb_strlen($embed['description']));
        $this->assertLessThanOrEqual(DiscordChannel::MAX_EMBED_FIELD_VALUE, mb_strlen($embed['fields'][0]['value']));
        $this->assertStringEndsWith(DiscordChannel::TRUNCATION_SUFFIX, $embed['title']);
    }

    /** @test */
    public function it_skips_sending_when_no_content_and_no_embeds()
    {
        $container = [];
        $channel = $this->makeChannel([], $container);

        $channel->send(null, $this->notificationStub(['content' => null, 'embeds' => []]));

        $this->assertCount(0, $container);
    }

    /**
     * Build a DiscordChannel wired to a Guzzle MockHandler so we can assert on requests.
     * `$container` is populated by reference with each transaction.
     */
    protected function makeChannel(array $responses, array &$container): DiscordChannel
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));

        $channel = new DiscordChannel();
        $ref = new \ReflectionClass($channel);
        $clientProp = $ref->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($channel, new Client(['handler' => $stack]));

        return $channel;
    }

    protected function notificationStub(array $message): Notification
    {
        $message = array_merge([
            'webhook_url' => 'https://discord.com/api/webhooks/1234567890/abcdefghijklmnopqrstuvwxyz',
            'content' => null,
            'embeds' => [],
            'username' => 'Test',
            'avatar_url' => null,
            'tts' => false,
        ], $message);

        return new class($message) extends Notification {
            public function __construct(private array $message) {}
            public function via($notifiable): array { return [DiscordChannel::class]; }
            public function toDiscord($notifiable): array { return $this->message; }
        };
    }
}
