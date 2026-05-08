<?php

declare(strict_types=1);

use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

describe('isDemoUser', function () {
    it('returns true only for the demo email', function (string $email, bool $expected) {
        $user = new User(['email' => $email]);

        expect($user->isDemoUser())->toBe($expected);
    })->with([
        'demo email' => ['johndoe@example.com', true],
        'other email' => ['alice@example.com', false],
        'empty email' => ['', false],
    ]);
});

describe('isProtectedDemoUser', function () {
    it('combines production environment and demo email', function (string $env, string $email, bool $expected) {
        app()->detectEnvironment(fn () => $env);
        $user = new User(['email' => $email]);

        expect($user->isProtectedDemoUser())->toBe($expected);
    })->with([
        'production + demo email → protected' => ['production', 'johndoe@example.com', true],
        'production + other email → not protected' => ['production', 'alice@example.com', false],
        'testing + demo email → not protected' => ['testing', 'johndoe@example.com', false],
        'local + demo email → not protected' => ['local', 'johndoe@example.com', false],
    ]);
});
