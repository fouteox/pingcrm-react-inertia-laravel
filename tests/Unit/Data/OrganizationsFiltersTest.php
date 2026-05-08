<?php

declare(strict_types=1);

use App\Data\OrganizationsFilters;
use App\Enums\TrashedFilter;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

describe('fromRequest', function () {
    it('returns null fields when no query params are provided', function () {
        $filters = OrganizationsFilters::fromRequest(Request::create('/organizations'));

        expect($filters->search)->toBeNull()
            ->and($filters->trashed)->toBeNull();
    });

    it('trims the search term', function (string $input, ?string $expected) {
        $filters = OrganizationsFilters::fromRequest(Request::create('/organizations', 'GET', ['search' => $input]));

        expect($filters->search)->toBe($expected);
    })->with([
        'plain term' => ['Apple', 'Apple'],
        'padded term' => ['  Apple  ', 'Apple'],
        'empty string' => ['', null],
        'whitespace only' => ['   ', null],
    ]);

    it('accepts only whitelisted trashed values', function (string $input, ?TrashedFilter $expected) {
        $filters = OrganizationsFilters::fromRequest(Request::create('/organizations', 'GET', ['trashed' => $input]));

        expect($filters->trashed)->toBe($expected);
    })->with([
        'with' => ['with', TrashedFilter::With],
        'only' => ['only', TrashedFilter::Only],
        'invalid word' => ['foo', null],
        'all' => ['all', null],
        'empty' => ['', null],
    ]);
});

describe('toArray', function () {
    it('is empty when all fields are null', function () {
        expect((new OrganizationsFilters)->toArray())->toBe([]);
    });

    it('omits null fields', function () {
        expect((new OrganizationsFilters(search: 'Apple'))->toArray())
            ->toBe(['search' => 'Apple']);
    });

    it('returns every field when all are set', function () {
        expect((new OrganizationsFilters(search: 'Apple', trashed: TrashedFilter::Only))->toArray())
            ->toBe(['search' => 'Apple', 'trashed' => 'only']);
    });
});

it('round-trips a Request through the DTO and back to an array', function () {
    $array = OrganizationsFilters::fromRequest(Request::create('/organizations', 'GET', [
        'search' => '  Apple  ',
        'trashed' => 'only',
        'ignored' => 'not kept',
    ]))->toArray();

    expect($array)->toBe(['search' => 'Apple', 'trashed' => 'only']);
});
