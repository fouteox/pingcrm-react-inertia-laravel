<?php

namespace Deployer;

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/../')->load();

desc('Inject all necessary .env variables inside deployer config');
task('deploy:env', function () {
    Dotenv::createImmutable(__DIR__ . '/../')->load();
    set('password', env('APP_SUDO_PWD'));
});
