<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\VersionedSearchDocuments;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\TypesenseClientError;

function versionedDocumentsTransport(array $responses, array &$history): VersionedSearchDocuments
{
    $handler = HandlerStack::create(new MockHandler($responses));
    $handler->push(Middleware::history($history));

    return new VersionedSearchDocuments(new Client([
        'api_key' => 'test-key',
        'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
        'num_retries' => 0,
        'client' => new HttpClient(['handler' => $handler]),
    ]));
}

it('creates a versioned searchable document using the native Typesense SDK', function () {
    $history = [];
    $documents = versionedDocumentsTransport([new Response(201, body: '{}')], $history);
    $user = (new User)->forceFill(['id' => 12, 'account_id' => 7, 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.test', 'owner' => true]);

    $documents->write($user, 7, 3);

    expect($history)->toHaveCount(1);
    $request = $history[0]['request'];
    $body = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
    expect($request->getMethod())->toBe('POST')
        ->and($body)->toMatchArray(['id' => '12', 'account_id' => 7, 'last_name' => 'Lovelace', 'search_revision' => 3, 'search_deleted' => false, '__soft_deleted' => 0]);
});

it('leaves missing collections unavailable instead of recreating an incomplete index', function () {
    $history = [];
    $documents = versionedDocumentsTransport([
        new Response(404, body: '{"message":"Collection not found"}'),
        new Response(201, body: '{}'),
        new Response(201, body: '{}'),
    ], $history);
    $user = (new User)->forceFill(['id' => 12, 'account_id' => 7, 'last_name' => 'Candidate']);

    expect(fn () => $documents->write($user, 7, 3))->toThrow(ObjectNotFound::class);
    expect($history)->toHaveCount(1);
});

it('conditionally replaces existing and unversioned documents without lowering a newer revision', function () {
    $history = [];
    $documents = versionedDocumentsTransport([
        new Response(409, body: '{"message":"already exists"}'),
        new Response(200, body: '{"num_updated":0}'),
        new Response(200, body: '{"id":"12","account_id":7,"search_revision":9,"search_deleted":false}'),
    ], $history);
    $user = (new User)->forceFill(['id' => 12, 'account_id' => 7, 'last_name' => 'Old']);

    $documents->write($user, 7, 3);

    expect($history)->toHaveCount(3);
    parse_str($history[1]['request']->getUri()->getQuery(), $query);
    expect($history[1]['request']->getMethod())->toBe('PATCH')
        ->and($query['filter_by'])->toBe('id:=12 && (search_revision:<3 || search_revision:!=[0..'.PHP_INT_MAX.'])');
});

it('does not acknowledge a rejected conditional update with an old revision', function () {
    $history = [];
    $documents = versionedDocumentsTransport([
        new Response(409, body: '{"message":"already exists"}'),
        new Response(200, body: '{"num_updated":0}'),
        new Response(200, body: '{"id":"12","account_id":7,"search_revision":2,"search_deleted":false}'),
    ], $history);

    expect(fn () => $documents->write((new User)->forceFill(['id' => 12]), 7, 3))
        ->toThrow(TypesenseClientError::class);
});

it('retains a versioned tombstone that stale creations cannot replace', function () {
    $history = [];
    $documents = versionedDocumentsTransport([
        new Response(409, body: '{"message":"already exists"}'),
        new Response(200, body: '{"num_updated":1}'),
    ], $history);

    $documents->write((new User)->forceFill(['id' => 12]), 7, 8, deleted: true);

    $body = json_decode((string) $history[1]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);
    expect($body)->toMatchArray(['id' => '12', 'account_id' => 7, 'search_revision' => 8, 'search_deleted' => true, '__soft_deleted' => 1])
        ->and($body['first_name'])->toBe('')
        ->and($body['last_name'])->toBe('')
        ->and($body['email'])->toBe('');
});

it('retrieves a bounded first page of obsolete IDs with native Typesense filters', function (int $limit) {
    $history = [];
    $ids = range(1, $limit);
    $model = new User;
    $documents = versionedDocumentsTransport([
        new Response(200, body: json_encode([
            'found' => 500,
            'hits' => array_map(fn (int $id): array => ['document' => ['id' => (string) $id]], $ids),
            'search_cutoff' => false,
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    expect($documents->staleIds($model, 7, 3, $limit))->toBe($ids);
    expect($history)->toHaveCount(1);
    $request = $history[0]['request'];
    parse_str($request->getUri()->getQuery(), $query);

    expect($request->getMethod())->toBe('GET')
        ->and($request->getUri()->getPath())->toBe('/collections/'.$model->indexableAs().'/documents/search')
        ->and($query)->toMatchArray([
            'q' => '*',
            'filter_by' => 'account_id:=7 && search_deleted:!=true && (search_revision:<3 || search_revision:!=[0..'.PHP_INT_MAX.'])',
            'include_fields' => 'id',
            'per_page' => (string) $limit,
            'page' => '1',
            'filter_curated_hits' => 'true',
            'enable_overrides' => 'false',
            'use_cache' => 'false',
        ])->toHaveCount(8);
})->with([1, 250]);

it('rejects an unbounded obsolete document page before contacting Typesense', function (int $limit) {
    $history = [];
    $documents = versionedDocumentsTransport([], $history);

    expect(fn () => $documents->staleIds(new User, 7, 3, $limit))->toThrow(InvalidArgumentException::class);
    expect($history)->toBeEmpty();
})->with([-1, 0, 251]);

it('does not accept a cut off stale document search as a complete rebuild page', function () {
    $history = [];
    $documents = versionedDocumentsTransport([
        new Response(200, body: '{"found":0,"hits":[],"search_cutoff":true}'),
    ], $history);

    expect(fn () => $documents->staleIds(new User, 7, 3, 100))->toThrow(TypesenseClientError::class);
    expect($history)->toHaveCount(1);
});

it('rejects obsolete documents whose model IDs cannot be validated', function (array $document) {
    $history = [];
    $documents = versionedDocumentsTransport([
        new Response(200, body: json_encode(['found' => 1, 'hits' => [['document' => $document]]], JSON_THROW_ON_ERROR)),
    ], $history);

    expect(fn () => $documents->staleIds(new User, 7, 3, 100))->toThrow(TypesenseClientError::class);
    expect($history)->toHaveCount(1);
})->with([
    'missing ID' => [[]],
    'nonnumeric ID' => [['id' => 'invalid']],
    'zero ID' => [['id' => '0']],
    'negative ID' => [['id' => '-12']],
    'fractional ID' => [['id' => '12.5']],
    'signed ID' => [['id' => '+12']],
    'padded ID' => [['id' => ' 12 ']],
    'overflowing ID' => [['id' => PHP_INT_MAX.'0']],
]);

it('rejects incomplete or malformed obsolete document search responses', function (array $response) {
    $history = [];
    $documents = versionedDocumentsTransport([
        new Response(200, body: json_encode($response, JSON_THROW_ON_ERROR)),
    ], $history);

    expect(fn () => $documents->staleIds(new User, 7, 3, 100))->toThrow(TypesenseClientError::class);
    expect($history)->toHaveCount(1);
})->with([
    'missing total' => [['hits' => []]],
    'noninteger total' => [['found' => '1', 'hits' => [['document' => ['id' => '12']]]]],
    'negative total' => [['found' => -1, 'hits' => []]],
    'missing hits' => [['found' => 1]],
    'nonlist hits' => [['found' => 1, 'hits' => ['document' => ['id' => '12']]]],
    'missing matching hits' => [['found' => 1, 'hits' => []]],
]);

it('does not treat a missing collection as an empty obsolete document page', function () {
    $history = [];
    $documents = versionedDocumentsTransport([new Response(404, body: '{"message":"missing collection"}')], $history);

    expect(fn () => $documents->staleIds(new User, 7, 3, 100))->toThrow(ObjectNotFound::class);
    expect($history)->toHaveCount(1);
});
