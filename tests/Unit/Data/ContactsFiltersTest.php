<?php

declare(strict_types=1);

use App\Data\ContactsFilters;
use App\Enums\TrashedFilter;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

describe('fromRequest', function () {
    it('returns null fields when no query params are provided', function () {
        $filters = ContactsFilters::fromRequest(Request::create('/contacts'));

        expect($filters->search)->toBeNull()
            ->and($filters->trashed)->toBeNull();
    });

    it('trims the search term', function (string $input, ?string $expected) {
        $filters = ContactsFilters::fromRequest(Request::create('/contacts', 'GET', ['search' => $input]));

        expect($filters->search)->toBe($expected);
    })->with([
        'plain term' => ['Martin', 'Martin'],
        'padded term' => ['  Martin  ', 'Martin'],
        'empty string' => ['', null],
        'whitespace only' => ['   ', null],
    ]);

    it('accepts only whitelisted trashed values', function (string $input, ?TrashedFilter $expected) {
        $filters = ContactsFilters::fromRequest(Request::create('/contacts', 'GET', ['trashed' => $input]));

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
        expect((new ContactsFilters)->toArray())->toBe([]);
    });

    it('omits null fields', function () {
        expect((new ContactsFilters(search: 'Martin'))->toArray())
            ->toBe(['search' => 'Martin']);
    });

    it('returns every field when all are set', function () {
        expect((new ContactsFilters(search: 'Martin', trashed: TrashedFilter::With))->toArray())
            ->toBe(['search' => 'Martin', 'trashed' => 'with']);
    });
});

it('round-trips a Request through the DTO and back to an array', function () {
    $array = ContactsFilters::fromRequest(Request::create('/contacts', 'GET', [
        'search' => '  Martin  ',
        'trashed' => 'with',
        'ignored' => 'not kept',
    ]))->toArray();

    expect($array)->toBe(['search' => 'Martin', 'trashed' => 'with']);
});
