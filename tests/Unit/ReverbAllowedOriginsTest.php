<?php

declare(strict_types=1);

use App\Support\ReverbAllowedOrigins;
use Laravel\Reverb\Application;
use Laravel\Reverb\Contracts\Connection;
use Laravel\Reverb\Contracts\WebSocketConnection;
use Laravel\Reverb\Protocols\Pusher\Contracts\ChannelManager;
use Laravel\Reverb\Protocols\Pusher\EventHandler;
use Laravel\Reverb\Protocols\Pusher\Server;
use Ratchet\RFC6455\Messaging\Frame;
use Tests\TestCase;

uses(TestCase::class);

final class ReverbOriginTestConnection extends Connection
{
    /** @var list<string> */
    public array $messages = [];

    public function __construct(Application $application, string $origin)
    {
        $socket = new class implements WebSocketConnection
        {
            public function id(): string
            {
                return 'test-socket';
            }

            public function send(mixed $message): void
            {
                // The Reverb connection adapter captures outgoing messages.
            }

            public function close(mixed $message = null): void
            {
                // Nothing to close in this in-memory protocol test.
            }
        };

        parent::__construct($socket, $application, $origin);
    }

    public function identifier(): string
    {
        return 'test-connection';
    }

    public function id(): string
    {
        return '1.1';
    }

    public function send(string $message): void
    {
        $this->messages[] = $message;
    }

    public function control(string $type = Frame::OP_PING): void
    {
        // Control frames are outside this origin-handshake test.
    }

    public function terminate(): void
    {
        // Nothing to terminate in this in-memory protocol test.
    }
}

function makeReverbTestApplication(array $allowedOrigins): Application
{
    return new Application(
        id: 'pingcrm',
        key: 'key',
        secret: 'secret',
        pingInterval: 60,
        activityTimeout: 30,
        allowedOrigins: $allowedOrigins,
        maxMessageSize: 10_000,
    );
}

it('normalizes an explicit exact origin allowlist', function () {
    expect(ReverbAllowedOrigins::fromEnvironment(
        origins: ' pingcrm.fadogen.app,ADMIN.FADOGEN.APP,pingcrm.fadogen.app ',
        environment: 'production',
        fallbackHost: 'pingcrm.fadogen.app',
    ))->toBe([
        'pingcrm.fadogen.app',
        'admin.fadogen.app',
    ]);
});

it('loads the parsed environment allowlist into the Reverb configuration', function () {
    $reverb = require config_path('reverb.php');
    $expected = ReverbAllowedOrigins::fromEnvironment(
        origins: env('REVERB_ALLOWED_ORIGINS'),
        environment: (string) env('APP_ENV', 'production'),
        fallbackHost: env('REVERB_HOST', 'localhost'),
    );

    expect($reverb['apps']['apps'][0]['allowed_origins'])->toBe($expected)
        ->not->toContain('*');
});

it('fails closed outside local development without an explicit origin allowlist', function (string $environment) {
    expect(ReverbAllowedOrigins::fromEnvironment(
        origins: null,
        environment: $environment,
        fallbackHost: 'pingcrm.fadogen.app',
    ))->toBe([]);
})->with(['production', 'staging']);

it('uses the exact Reverb host as the local development default', function () {
    expect(ReverbAllowedOrigins::fromEnvironment(
        origins: null,
        environment: 'local',
        fallbackHost: 'reverb.dev.localhost',
    ))->toBe(['reverb.dev.localhost']);
});

it('rejects unsafe or ambiguous explicit origin values', function (string $origins) {
    expect(fn (): array => ReverbAllowedOrigins::fromEnvironment(
        origins: $origins,
        environment: 'production',
        fallbackHost: 'pingcrm.fadogen.app',
    ))->toThrow(InvalidArgumentException::class);
})->with([
    'empty' => '',
    'empty list' => ' , ',
    'global wildcard' => '*',
    'subdomain wildcard' => '*.fadogen.app',
    'URL' => 'https://pingcrm.fadogen.app',
    'port' => 'pingcrm.fadogen.app:443',
    'opaque origin' => 'null',
    'trailing dot' => 'pingcrm.fadogen.app.',
]);

it('allows the configured origin through the installed Reverb server', function () {
    $application = makeReverbTestApplication(['pingcrm.fadogen.app']);
    $channels = Mockery::mock(ChannelManager::class);
    $handler = new EventHandler($channels);
    $connection = new ReverbOriginTestConnection(
        $application,
        'https://pingcrm.fadogen.app:443',
    );

    (new Server($channels, $handler))->open($connection);

    expect($connection->messages)->toHaveCount(1)
        ->and(json_decode($connection->messages[0], true, flags: JSON_THROW_ON_ERROR))
        ->toMatchArray(['event' => 'pusher:connection_established']);
});

it('rejects a lookalike origin through the installed Reverb server', function () {
    $application = makeReverbTestApplication(['pingcrm.fadogen.app']);
    $channels = Mockery::mock(ChannelManager::class);
    $handler = new EventHandler($channels);
    $connection = new ReverbOriginTestConnection(
        $application,
        'https://pingcrm.fadogen.app.attacker.example',
    );
    $expectedPayload = json_encode([
        'event' => 'pusher:error',
        'data' => json_encode([
            'code' => 4009,
            'message' => 'Origin not allowed',
        ]),
    ]);

    (new Server($channels, $handler))->open($connection);

    expect($connection->messages)->toBe([$expectedPayload]);
});
