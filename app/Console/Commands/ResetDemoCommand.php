<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'demo:reset', description: 'Reset the demo database and reindex search data')]
final class ResetDemoCommand extends Command
{
    public function handle(): int
    {
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->info('Importing search indexes...');
        Contact::makeAllSearchable();
        Organization::makeAllSearchable();
        User::makeAllSearchable();
        $this->info('Search indexes imported.');

        return self::SUCCESS;
    }
}
