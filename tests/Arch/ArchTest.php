<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\Finder\Finder;

test('strict types everywhere in app/', function () {
    $offenders = [];

    foreach (Finder::create()->in(dirname(__DIR__, 2).'/app')->files()->name('*.php') as $file) {
        $content = (string) file_get_contents($file->getRealPath());

        if (preg_match('/^<\?php\s*(\/\*[\s\S]*?\*\/|\/\/[^\r\n]*(?:\r?\n|$)|\s)*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/m', $content) !== 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBeEmpty();
});

arch('no debug helpers shipped to production')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();

arch('app does not depend on test tooling')
    ->expect('App')
    ->not->toUse(['Tests', 'PHPUnit', 'Pest']);

describe('Controllers', function () {
    arch('are final')
        ->expect('App\Http\Controllers')
        ->classes()
        ->toBeFinal()
        ->ignoring(Controller::class);

    arch('have the Controller suffix')
        ->expect('App\Http\Controllers')
        ->toHaveSuffix('Controller');

    arch('extend the base Controller')
        ->expect('App\Http\Controllers')
        ->classes()
        ->toExtend(Controller::class)
        ->ignoring(Controller::class);
});

describe('Models', function () {
    arch('are final')
        ->expect('App\Models')
        ->classes()
        ->toBeFinal();

    arch('extend Eloquent or Authenticatable')
        ->expect('App\Models')
        ->classes()
        ->toExtend(Model::class)
        ->ignoring(User::class);
});

describe('Policies', function () {
    arch('are final')
        ->expect('App\Policies')
        ->classes()
        ->toBeFinal();

    arch('have the Policy suffix')
        ->expect('App\Policies')
        ->toHaveSuffix('Policy');
});

describe('Data Transfer Objects', function () {
    arch('are final and readonly')
        ->expect('App\Data')
        ->classes()
        ->toBeFinal()
        ->toBeReadonly();
});

describe('Form Requests', function () {
    arch('extend FormRequest')
        ->expect('App\Http\Requests')
        ->classes()
        ->toExtend(FormRequest::class);

    arch('have the Request suffix')
        ->expect('App\Http\Requests')
        ->toHaveSuffix('Request');
});

describe('Resources', function () {
    arch('extend JsonResource')
        ->expect('App\Http\Resources')
        ->classes()
        ->toExtend(JsonResource::class);
});

arch('User model extends Authenticatable')
    ->expect(User::class)
    ->toExtend(Authenticatable::class);
