<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Scout\EngineManager;
use Psr\Http\Message\RequestInterface;
use Typesense\Client;

it('serves search without running or enqueuing projection work during the read', function (string $modelClass, string $resource) {
    $actor = User::factory()->create(['owner' => true]);
    $field = $modelClass === Organization::class ? 'name' : 'last_name';
    $model = $modelClass::factory()->for($actor->account)->create([$field => 'Before']);
    config()->set(['scout.driver' => 'typesense', 'scout.prefix' => '', 'search.synchronous' => false]);
    app(EngineManager::class)->forgetEngines();
    app(SearchIndex::class)->mutate($actor->account_id, fn () => tap($model)->update([$field => 'After']));
    expect(DB::table('jobs')->count())->toBe(1);

    $handler = new MockHandler([
        function (RequestInterface $request) use ($model, $resource): Response {
            expect($request->getMethod())->toBe('GET')
                ->and($request->getUri()->getPath())->toBe("/collections/{$resource}/documents/search");

            return new Response(200, body: json_encode([
                'found' => 1,
                'hits' => [['document' => ['id' => (string) $model->id]]],
            ], JSON_THROW_ON_ERROR));
        },
    ]);
    app()->instance(Client::class, new Client([
        'api_key' => 'test-key',
        'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
        'num_retries' => 0,
        'client' => new HttpClient(['handler' => HandlerStack::create($handler)]),
    ]));
    $writes = [];
    DB::listen(function (QueryExecuted $query) use (&$writes): void {
        if (preg_match('/^\s*(?:insert|update|delete)\b/i', $query->sql) === 1) {
            $writes[] = $query->sql;
        }
    });

    $this->actingAs($actor)->get("/{$resource}?search=Before")
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert->has("{$resource}.data", 1));

    expect($writes)->toBe([])
        ->and(DB::table('jobs')->count())->toBe(1)
        ->and($handler->count())->toBe(0);
})->with([
    'contacts' => [Contact::class, 'contacts'],
    'organizations' => [Organization::class, 'organizations'],
    'users' => [User::class, 'users'],
]);
