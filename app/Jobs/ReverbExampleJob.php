<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\ReverbExampleEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ReverbExampleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $uuid, public string $locale) {}

    public function handle(): void
    {
        sleep(5);

        event(new ReverbExampleEvent($this->uuid, $this->locale));
    }
}
