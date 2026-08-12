<?php

declare(strict_types=1);

use App\Console\Commands\ResetDemoCommand;
use App\Jobs\ReconcileDemoSearchIndex;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

it('resets only the demo tenant without rebuilding the schema or identities', function () {
    config()->set([
        'app.key' => 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=',
        'broadcasting.connections.reverb.key' => 'testing-key',
        'scout.queue' => false,
    ]);

    Schema::create('deployment_sentinel', function (Blueprint $table): void {
        $table->id();
        $table->string('value');
    });
    DB::table('deployment_sentinel')->insert(['value' => 'preserved']);
    Cache::put('demo-reset-sentinel', 'preserved', 600);
    Storage::fake('local');
    Storage::disk('local')->put('demo-reset-sentinel.txt', 'preserved');

    $otherAccount = Account::factory()->create(['name' => 'Customer account']);
    $otherUser = User::factory()->create(['account_id' => $otherAccount->id]);
    $otherOrganization = Organization::factory()->create(['account_id' => $otherAccount->id]);
    $otherContact = Contact::factory()->create([
        'account_id' => $otherAccount->id,
        'organization_id' => $otherOrganization->id,
    ]);

    $demoAccount = Account::factory()->create([
        'name' => 'Renamed demo',
        'demo_key' => DatabaseSeeder::DEMO_ACCOUNT_KEY,
    ]);
    $demoUser = User::factory()->create([
        'account_id' => $demoAccount->id,
        'email' => 'johndoe@example.com',
        'first_name' => 'Changed',
        'last_name' => 'User',
        'password' => Hash::make('secret'),
        'owner' => false,
    ]);
    $demoPassword = $demoUser->password;
    $obsoleteUser = User::factory()->create(['account_id' => $demoAccount->id]);
    $obsoleteOrganization = Organization::factory()->create(['account_id' => $demoAccount->id]);
    $obsoleteContact = Contact::factory()->create([
        'account_id' => $demoAccount->id,
        'organization_id' => $obsoleteOrganization->id,
    ]);

    $highestUserId = User::max('id');
    $highestOrganizationId = Organization::max('id');
    $highestContactId = Contact::max('id');

    $this->post(route('login.store'), [
        'email' => 'johndoe@example.com',
        'password' => 'secret',
    ])->assertRedirect(route('dashboard', absolute: false));
    Auth::forgetGuards();

    $this->artisan('demo:reset')->assertSuccessful();

    Auth::forgetGuards();
    $this->get('/')->assertSuccessful();
    $this->assertAuthenticatedAs(User::findOrFail($demoUser->id));

    expect(Schema::hasTable('deployment_sentinel'))->toBeTrue()
        ->and(DB::table('deployment_sentinel')->value('value'))->toBe('preserved')
        ->and(Cache::get('demo-reset-sentinel'))->toBe('preserved')
        ->and(Storage::disk('local')->get('demo-reset-sentinel.txt'))->toBe('preserved')
        ->and(Account::findOrFail($otherAccount->id)->name)->toBe('Customer account')
        ->and(User::findOrFail($otherUser->id)->account_id)->toBe($otherAccount->id)
        ->and(Organization::findOrFail($otherOrganization->id)->account_id)->toBe($otherAccount->id)
        ->and(Contact::findOrFail($otherContact->id)->account_id)->toBe($otherAccount->id)
        ->and(Account::findOrFail($demoAccount->id)->name)->toBe('Acme Corporation');

    $preservedDemoUser = User::findOrFail($demoUser->id);

    expect($preservedDemoUser->account_id)->toBe($demoAccount->id)
        ->and($preservedDemoUser->first_name)->toBe('John')
        ->and($preservedDemoUser->last_name)->toBe('Doe')
        ->and($preservedDemoUser->owner)->toBeTruthy()
        ->and($preservedDemoUser->password)->toBe($demoPassword)
        ->and(Hash::check('secret', $preservedDemoUser->password))->toBeTrue()
        ->and(User::whereBelongsTo($demoAccount)->count())->toBe(6)
        ->and(Organization::whereBelongsTo($demoAccount)->count())->toBe(100)
        ->and(Contact::whereBelongsTo($demoAccount)->count())->toBe(100)
        ->and(User::whereBelongsTo($demoAccount)->whereKeyNot($demoUser->id)->min('id'))->toBeGreaterThan($highestUserId)
        ->and(Organization::whereBelongsTo($demoAccount)->min('id'))->toBeGreaterThan($highestOrganizationId)
        ->and(Contact::whereBelongsTo($demoAccount)->min('id'))->toBeGreaterThan($highestContactId)
        ->and(User::find($obsoleteUser->id))->toBeNull()
        ->and(Organization::find($obsoleteOrganization->id))->toBeNull()
        ->and(Contact::find($obsoleteContact->id))->toBeNull();
});

it('invalidates a removed user session without ever reusing its identity', function () {
    config()->set([
        'app.key' => 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=',
        'broadcasting.connections.reverb.key' => 'testing-key',
        'scout.queue' => false,
    ]);

    $account = Account::factory()->create([
        'demo_key' => DatabaseSeeder::DEMO_ACCOUNT_KEY,
    ]);
    User::factory()->create([
        'account_id' => $account->id,
        'email' => DatabaseSeeder::DEMO_USER_EMAIL,
    ]);
    $removedUser = User::factory()->create([
        'account_id' => $account->id,
        'password' => Hash::make('secret'),
    ]);
    $highestUserId = User::max('id');

    $this->post(route('login.store'), [
        'email' => $removedUser->email,
        'password' => 'secret',
    ])->assertRedirect(route('dashboard', absolute: false));
    Auth::forgetGuards();

    $this->artisan('demo:reset')->assertSuccessful();

    Auth::forgetGuards();
    $this->get('/')->assertRedirect(route('login', absolute: false));
    $this->assertGuest();

    expect(User::find($removedUser->id))->toBeNull()
        ->and(User::whereBelongsTo($account)
            ->where('email', '!=', DatabaseSeeder::DEMO_USER_EMAIL)
            ->min('id'))
        ->toBeGreaterThan($highestUserId);
});

it('rolls the complete reset back before dispatching search work when seeding fails', function () {
    $demoAccount = Account::factory()->create([
        'name' => 'Demo before failure',
        'demo_key' => DatabaseSeeder::DEMO_ACCOUNT_KEY,
    ]);
    $demoUser = User::factory()->create([
        'account_id' => $demoAccount->id,
        'email' => 'johndoe@example.com',
        'first_name' => 'Before',
    ]);
    $organization = Organization::factory()->create(['account_id' => $demoAccount->id]);
    $contact = Contact::factory()->create([
        'account_id' => $demoAccount->id,
        'organization_id' => $organization->id,
    ]);

    Queue::fake();

    $creatingOrganization = 'eloquent.creating: '.Organization::class;
    Event::listen($creatingOrganization, fn () => throw new RuntimeException('Injected seeding failure'));

    try {
        expect(fn () => $this->artisan('demo:reset')->run())
            ->toThrow(RuntimeException::class, 'Injected seeding failure');
    } finally {
        Event::forget($creatingOrganization);
    }

    $releasedLock = Cache::lock(
        ResetDemoCommand::LOCK_NAME,
        (int) config('demo.reset.lock_seconds')
    );

    expect(Account::findOrFail($demoAccount->id)->name)->toBe('Demo before failure')
        ->and(User::findOrFail($demoUser->id)->first_name)->toBe('Before')
        ->and(Organization::findOrFail($organization->id)->account_id)->toBe($demoAccount->id)
        ->and(Contact::findOrFail($contact->id)->organization_id)->toBe($organization->id)
        ->and($releasedLock->get())->toBeTrue();

    $releasedLock->release();

    Queue::assertNothingPushed();
});

it('never adopts an unrelated account that has the canonical demo name', function () {
    config()->set('scout.queue', false);

    $unrelatedAccount = Account::factory()->create(['name' => 'Acme Corporation']);
    $unrelatedUser = User::factory()->create(['account_id' => $unrelatedAccount->id]);
    $unrelatedOrganization = Organization::factory()->create(['account_id' => $unrelatedAccount->id]);

    $this->artisan('demo:reset')->assertSuccessful();

    $demoUser = User::where('email', 'johndoe@example.com')->sole();

    expect($demoUser->account_id)->not->toBe($unrelatedAccount->id)
        ->and(Account::findOrFail($unrelatedAccount->id)->name)->toBe('Acme Corporation')
        ->and(User::findOrFail($unrelatedUser->id)->account_id)->toBe($unrelatedAccount->id)
        ->and(Organization::findOrFail($unrelatedOrganization->id)->account_id)->toBe($unrelatedAccount->id);
});

it('fails safe instead of adopting an unmarked account through the demo email', function () {
    $unmarkedAccount = Account::factory()->create();
    $user = User::factory()->create([
        'account_id' => $unmarkedAccount->id,
        'email' => DatabaseSeeder::DEMO_USER_EMAIL,
    ]);

    Queue::fake();

    expect(fn () => $this->artisan('demo:reset')->run())
        ->toThrow(
            UnexpectedValueException::class,
            'The canonical demo email exists without a marked demo account.'
        );

    expect(Account::findOrFail($unmarkedAccount->id)->demo_key)->toBeNull()
        ->and(User::findOrFail($user->id)->account_id)->toBe($unmarkedAccount->id);

    Queue::assertNothingPushed();
});

it('aborts without mutation when a cross-tenant organization reference exists', function () {
    $demoAccount = Account::factory()->create([
        'demo_key' => DatabaseSeeder::DEMO_ACCOUNT_KEY,
    ]);
    $demoUser = User::factory()->create([
        'account_id' => $demoAccount->id,
        'email' => 'johndoe@example.com',
    ]);
    $demoOrganization = Organization::factory()->create(['account_id' => $demoAccount->id]);
    $otherAccount = Account::factory()->create();
    $crossTenantContact = Contact::factory()->create([
        'account_id' => $otherAccount->id,
        'organization_id' => $demoOrganization->id,
    ]);

    Queue::fake();

    expect(fn () => $this->artisan('demo:reset')->run())
        ->toThrow(
            UnexpectedValueException::class,
            'Demo reset aborted: a contact references an organization from another account.'
        );

    expect(User::findOrFail($demoUser->id)->account_id)->toBe($demoAccount->id)
        ->and(Organization::findOrFail($demoOrganization->id)->account_id)->toBe($demoAccount->id)
        ->and(Contact::findOrFail($crossTenantContact->id)->organization_id)->toBe($demoOrganization->id);

    Queue::assertNothingPushed();
});

it('queues an authoritative search reconciliation after the database commit', function () {
    $account = Account::factory()->create([
        'demo_key' => DatabaseSeeder::DEMO_ACCOUNT_KEY,
    ]);
    User::factory()->create([
        'account_id' => $account->id,
        'email' => 'johndoe@example.com',
    ]);
    User::factory()->create(['account_id' => $account->id]);
    $obsoleteOrganization = Organization::factory()->create(['account_id' => $account->id]);
    Contact::factory()->create([
        'account_id' => $account->id,
        'organization_id' => $obsoleteOrganization->id,
    ]);

    Queue::fake();

    $this->artisan('demo:reset')->assertSuccessful();

    Queue::assertPushed(ReconcileDemoSearchIndex::class, 1);
    Queue::assertPushed(
        ReconcileDemoSearchIndex::class,
        fn (ReconcileDemoSearchIndex $job): bool => $job->accountId === $account->id
    );
});

it('rejects overlapping manual resets without mutating data', function () {
    $account = Account::factory()->create(['name' => 'Unchanged']);
    $lock = Cache::lock(
        ResetDemoCommand::LOCK_NAME,
        (int) config('demo.reset.lock_seconds')
    );

    expect($lock->get())->toBeTrue();

    try {
        $this->artisan('demo:reset')
            ->expectsOutput('Another demo reset is already running.')
            ->assertFailed();

        expect(Account::findOrFail($account->id)->name)->toBe('Unchanged');
    } finally {
        $lock->release();
    }
});

it('schedules the reset only in production with distributed overlap protection', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains($event->command ?? '', 'demo:reset'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *')
        ->and($event->environments)->toBe(['production'])
        ->and($event->onOneServer)->toBeTrue()
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(10)
        ->and(config('scout.after_commit'))->toBeTrue();
});

it('keeps search retries longer than the reset lock and below the Redis retry window', function () {
    $reflection = new ReflectionClass(ReconcileDemoSearchIndex::class);
    $tries = $reflection->getAttributes(Tries::class)[0]->newInstance()->tries;
    $timeout = $reflection->getAttributes(Timeout::class)[0]->newInstance()->timeout;
    $middleware = (new ReconcileDemoSearchIndex(1))->middleware()[0];

    expect($timeout)->toBeLessThan(config('queue.connections.redis.retry_after'))
        ->and($middleware->releaseAfter)->toBe(60)
        ->and($middleware->expiresAfter)->toBe((int) config('demo.reset.lock_seconds'))
        ->and($tries * $middleware->releaseAfter)->toBeGreaterThan($middleware->expiresAfter);
});
