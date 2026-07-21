<?php

declare(strict_types=1);

use App\Jobs\ReverbExampleJob;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->for(Account::create(['name' => 'Acme']))->create();
});

it('renders the reverb example page for authenticated users', function () {
    config()->set([
        'broadcasting.connections.reverb.key' => 'runtime-public-key',
        'broadcasting.connections.reverb.options.host' => 'reverb.example.test',
        'broadcasting.connections.reverb.options.port' => '8443',
        'broadcasting.connections.reverb.options.scheme' => 'https',
    ]);

    $this->actingAs($this->user)
        ->get('/reverb')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('reverb-example')
            ->where('reverb', [
                'key' => 'runtime-public-key',
                'host' => 'reverb.example.test',
                'port' => 8443,
                'scheme' => 'https',
            ]));
});

it('dispatches the reverb job with a delay when a valid uuid is posted', function () {
    Queue::fake();

    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    $this->actingAs($this->user)
        ->post('/reverb', ['uuid' => $uuid])
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(ReverbExampleJob::class, function ($job) use ($uuid) {
        return $job->uuid === $uuid && $job->delay !== null;
    });
});

it('pushes the uuid into the session for channel authorization', function () {
    Queue::fake();

    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    $this->actingAs($this->user)
        ->post('/reverb', ['uuid' => $uuid])
        ->assertSessionHas('reverb_uuids', fn (array $uuids) => in_array($uuid, $uuids, true));
});

it('rejects a missing or invalid uuid', function (array $payload) {
    Queue::fake();

    $this->actingAs($this->user)
        ->from('/reverb')
        ->post('/reverb', $payload)
        ->assertInvalid(['uuid']);

    Queue::assertNothingPushed();
})->with([
    'missing uuid' => [[]],
    'invalid uuid format' => [['uuid' => 'not-a-uuid']],
]);
