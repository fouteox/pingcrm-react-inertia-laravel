<?php

declare(strict_types=1);

use App\Console\Commands\ResetDemoCommand;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\CacheCommandMutex;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;

beforeEach(function () {
    $this->searchEngine = new class extends NullEngine
    {
        public array $records = [
            Contact::class => ['stale' => ['id' => 'stale']],
            Organization::class => ['stale' => ['id' => 'stale']],
            User::class => ['stale' => ['id' => 'stale']],
        ];

        public bool $failImports = false;

        /** @param Collection<int, Model> $models */
        public function update($models): void
        {
            if ($this->failImports) {
                throw new RuntimeException('Search index unavailable.');
            }

            foreach ($models as $model) {
                $this->records[$model::class][(string) $model->getKey()] = $model->toSearchableArray();
            }
        }

        public function flush($model): void
        {
            $this->records[$model::class] = [];
        }
    };

    $engine = $this->searchEngine;
    app(EngineManager::class)->extend('reset-test', static fn () => $engine);
    config()->set('scout.driver', 'reset-test');
});

it('resets demo records while preserving infrastructure and synchronously replacing search data', function () {
    $formerUser = User::factory()->create();
    DB::table('cache')->insert(['key' => 'preserved', 'value' => 'infrastructure', 'expiration' => 123]);
    DB::table('sessions')->insert(['id' => 'old-session', 'payload' => 'old', 'last_activity' => 123]);
    DB::table('password_reset_tokens')->insert(['email' => $formerUser->email, 'token' => 'old']);
    config()->set('scout.queue', true);
    Queue::fake();

    $this->artisan('demo:reset')->assertSuccessful();

    $this->assertDatabaseMissing('users', ['id' => $formerUser->id]);
    $this->assertDatabaseHas('cache', ['key' => 'preserved', 'value' => 'infrastructure']);
    $this->assertDatabaseCount('sessions', 0);
    $this->assertDatabaseCount('password_reset_tokens', 0);
    $this->assertDatabaseCount('accounts', 1);
    $this->assertDatabaseCount('contacts', 100);
    $this->assertDatabaseCount('organizations', 100);
    $this->assertDatabaseCount('users', 6);
    expect(User::where('email', 'johndoe@example.com')->firstOrFail()->isDemoUser())->toBeTrue();

    foreach ([Contact::class, Organization::class, User::class] as $model) {
        expect($this->searchEngine->records[$model])
            ->not->toHaveKey('stale')
            ->toHaveCount($model::count());
    }

    Queue::assertNothingPushed();
    expect(config('scout.queue'))->toBeTrue();
});

it('rolls back database changes and leaves search intact when seeding fails', function () {
    $formerUser = User::factory()->create();
    $beforeSearch = $this->searchEngine->records;
    app(Kernel::class)->registerCommand(new class extends Command
    {
        protected $signature = 'db:seed {--force} {--class=} {--database=}';

        public function handle(): int
        {
            Account::create(['name' => 'Incomplete seed']);

            return 7;
        }
    });

    $this->artisan('demo:reset')->assertFailed();

    $this->assertModelExists($formerUser);
    $this->assertDatabaseMissing('accounts', ['name' => 'Incomplete seed']);
    expect($this->searchEngine->records)->toBe($beforeSearch);
});

it('does not report success if synchronous indexing fails', function () {
    $this->searchEngine->failImports = true;
    Queue::fake();

    expect(fn () => $this->artisan('demo:reset')->run())
        ->toThrow(RuntimeException::class, 'Search index unavailable.');

    $this->assertDatabaseCount('users', 6);
    $this->assertDatabaseCount('contacts', 100);
    Queue::assertNothingPushed();
});

it('refuses overlapping manual resets using the native command lock', function () {
    $user = User::factory()->create();
    $command = app(ResetDemoCommand::class);
    $mutex = app(CacheCommandMutex::class);
    expect($mutex->create($command))->toBeTrue();

    try {
        $this->artisan('demo:reset')->assertFailed();
        $this->assertModelExists($user);
    } finally {
        $mutex->forget($command);
    }
});

it('keeps the demo reset hourly without overlapping scheduled runs', function () {
    $event = collect(app(Schedule::class)->events())
        ->sole(fn ($event) => str_contains($event->command ?? '', 'demo:reset'));

    expect($event->expression)->toBe('0 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});
