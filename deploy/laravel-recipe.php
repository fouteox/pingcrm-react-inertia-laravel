<?php

namespace Deployer;

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/../')->load();

desc('Inject all necessary .env variables inside deployer config');
task('deploy:env', function () {
    Dotenv::createImmutable(__DIR__ . '/../')->load();
    set('remote_user', env('APP_USER'));
    set('hostname', env('APP_IP'));
    set('port', env('APP_PORT'));
});
