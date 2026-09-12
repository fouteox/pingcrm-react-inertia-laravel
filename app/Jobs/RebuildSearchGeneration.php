<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\SearchIndexUnavailable;
use App\Services\SearchGenerations;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Tries(0)]
#[MaxExceptions(5)]
#[Backoff(5, 15, 30, 60, 120)]
#[Timeout(120)]
final class RebuildSearchGeneration implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $generation) {}

    public function handle(SearchGenerations $generations): void
    {
        $delay = 0;

        try {
            if ($generations->project($this->generation)) {
                return;
            }
        } catch (SearchIndexUnavailable) {
            $delay = (int) config('search.retry_delay');
        }

        $this->prependToChain((new self($this->generation))
            ->onConnection('search-index')->onQueue('search-index')->beforeCommit()->delay($delay));
    }
}
