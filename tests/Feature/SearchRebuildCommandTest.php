<?php

declare(strict_types=1);

use App\Jobs\SearchIndexProjection;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

it('durably schedules an account rebuild without touching other accounts', function () {
    config()->set('scout.driver', 'typesense');
    $account = Account::factory()->create();
    $other = Account::factory()->create();

    $this->artisan('search:rebuild', ['account' => $account->id])->assertSuccessful();

    $job = unserialize(json_decode(DB::table('jobs')->sole()->payload, true, flags: JSON_THROW_ON_ERROR)['data']['command']);
    expect($job)->toBeInstanceOf(SearchIndexProjection::class)
        ->and($job->accountId)->toBe($account->id)
        ->and($job->modelClass)->toBeNull()
        ->and($account->fresh()->search_revision)->toBe(1)
        ->and($other->fresh()->search_revision)->toBe(0);
});

it('refuses an unsupported search driver without scheduling work', function () {
    $this->artisan('search:rebuild')->assertFailed();
    expect(DB::table('jobs')->count())->toBe(0);
});

it('rejects an unknown or malformed account without rebuilding everyone', function (string $account) {
    config()->set('scout.driver', 'typesense');
    Account::factory()->create();
    $this->artisan('search:rebuild', ['account' => $account])->assertFailed();
    expect(DB::table('jobs')->count())->toBe(0);
})->with(['9999999', 'invalid', '0']);
