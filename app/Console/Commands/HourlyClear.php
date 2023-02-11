<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class HourlyClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:hourly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear DB and storage every hourly.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Artisan::call('down');

        Storage::deleteDirectory('users');
        Storage::deleteDirectory('.glide-cache');
        Artisan::call('migrate:fresh --force --seed');
        Artisan::call('up');

        return Command::SUCCESS;
    }
}
