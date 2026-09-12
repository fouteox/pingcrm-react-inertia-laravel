<?php

declare(strict_types=1);

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Symfony\Component\Process\Process;
use Typesense\Client;

it('bounds Typesense requests while the search server is still preparing its response', function () {
    $server = new Process([PHP_BINARY, '-r', <<<'SERVER'
        $server = stream_socket_server('tcp://127.0.0.1:0', $code, $message);

        if ($server === false) {
            fwrite(STDERR, $message);
            exit(1);
        }

        fwrite(STDOUT, 'address='.stream_socket_get_name($server, false)."\n");
        $connection = stream_socket_accept($server, 2);

        if ($connection === false) {
            exit(2);
        }

        stream_set_timeout($connection, 2);
        fwrite(STDOUT, fgets($connection));

        while (($header = fgets($connection)) !== false && trim($header) !== '') {
        }

        fwrite(STDOUT, "request-received\n");
        usleep(1_000_000);
        fwrite(STDOUT, "response-started\n");
        $body = json_encode(['results' => [['found' => 0, 'hits' => []]]]);
        fwrite($connection, "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: ".strlen($body)."\r\nConnection: close\r\n\r\n".$body);
        fclose($connection);
        fclose($server);
        SERVER], timeout: 3);
    $server->start();

    try {
        $output = '';
        $address = null;
        $ready = $server->waitUntil(function (string $type, string $buffer) use (&$output, &$address): bool {
            if ($type === Process::OUT) {
                $output .= $buffer;
            }

            if (preg_match('/^address=(127\.0\.0\.1:[0-9]+)$/m', $output, $matches) === 1) {
                $address = $matches[1];

                return true;
            }

            return false;
        });
        expect($ready)->toBeTrue();
        [$host, $port] = explode(':', $address);
        config()->set('scout.typesense.client-settings', [
            'api_key' => 'local-test-key',
            'nodes' => [['host' => $host, 'port' => $port, 'protocol' => 'http']],
            'connection_timeout_seconds' => 0.1,
            'request_timeout_seconds' => 0.1,
            'num_retries' => 0,
        ]);

        $exception = null;
        $startedAt = hrtime(true);

        try {
            app(Client::class)->getMultiSearch()->perform([
                'searches' => [['collection' => 'contacts', 'q' => 'delayed', 'query_by' => 'first_name']],
            ]);
        } catch (ClientExceptionInterface $caught) {
            $exception = $caught;
        }

        $elapsed = (hrtime(true) - $startedAt) / 1_000_000_000;
        $output = $server->getOutput();

        expect($exception)->toBeInstanceOf(NetworkExceptionInterface::class)
            ->and($output)->toContain('POST /multi_search', 'request-received')
            ->not->toContain('response-started')
            ->and($elapsed)->toBeLessThan(0.8);
    } finally {
        $server->stop(0);
    }
});
