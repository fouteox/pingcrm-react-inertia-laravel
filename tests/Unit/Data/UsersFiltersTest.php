<?php

declare(strict_types=1);

use App\Data\UsersFilters;
use App\Enums\Role;
use App\Enums\TrashedFilter;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

describe('fromRequest', function () {
    it('returns null fields when no query params are provided', function () {
        $filters = UsersFilters::fromRequest(Request::create('/users'));

        expect($filters->search)->toBeNull()
            ->and($filters->role)->toBeNull()
            ->and($filters->trashed)->toBeNull();
    });

    it('trims the search term', function (string $input, ?string $expected) {
        $filters = UsersFilters::fromRequest(Request::create('/users', 'GET', ['search' => $input]));

        expect($filters->search)->toBe($expected);
    })->with([
        'plain term' => ['Alice', 'Alice'],
        'padded term' => ['  Alice  ', 'Alice'],
        'empty string' => ['', null],
        'whitespace only' => ['   ', null],
    ]);

    it('accepts only whitelisted role values', function (string $input, ?Role $expected) {
        $filters = UsersFilters::fromRequest(Request::create('/users', 'GET', ['role' => $input]));

        expect($filters->role)->toBe($expected);
    })->with([
        'user' => ['user', Role::User],
        'owner' => ['owner', Role::Owner],
        'admin is not a valid role' => ['admin', null],
        'capitalized (strict match)' => ['Owner', null],
        'empty' => ['', null],
    ]);

    it('accepts only whitelisted trashed values', function (string $input, ?TrashedFilter $expected) {
        $filters = UsersFilters::fromRequest(Request::create('/users', 'GET', ['trashed' => $input]));

        expect($filters->trashed)->toBe($expected);
    })->with([
        'with' => ['with', TrashedFilter::With],
        'only' => ['only', TrashedFilter::Only],
        'invalid' => ['foo', null],
        'empty' => ['', null],
    ]);
});

describe('toArray', function () {
    it('is empty when all fields are null', function () {
        expect((new UsersFilters)->toArray())->toBe([]);
    });

    it('omits null fields', function () {
        expect(new UsersFilters(role: Role::Owner)->toArray())
            ->toBe(['role' => 'owner']);
    });

    it('returns every field when all are set', function () {
        expect(new UsersFilters(search: 'Alice', role: Role::Owner, trashed: TrashedFilter::With)->toArray())
            ->toBe([
                'search' => 'Alice',
                'role' => 'owner',
                'trashed' => 'with',
            ]);
    });
});

it('round-trips a Request through the DTO and back to an array', function () {
    $array = UsersFilters::fromRequest(Request::create('/users', 'GET', [
        'search' => '  Alice  ',
        'role' => 'owner',
        'trashed' => 'with',
        'ignored' => 'not kept',
    ]))->toArray();

    expect($array)->toBe([
        'search' => 'Alice',
        'role' => 'owner',
        'trashed' => 'with',
    ]);
});
