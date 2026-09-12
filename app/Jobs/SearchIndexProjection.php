<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\SearchIndexUnavailable;
use App\Services\SearchIndex;
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
final class SearchIndexProjection implements ShouldQueue
{
    use Queueable;

    /**
     * @param  class-string<\App\Models\Contact|\App\Models\Organization|\App\Models\User>|null  $modelClass
     */
    public function __construct(
        public int $accountId,
        public int $revision,
        public ?string $modelClass = null,
        public ?int $modelId = null,
    ) {}

    public function handle(SearchIndex $search): void
    {
        try {
            if (! $search->project($this)) {
                $this->continueProjection();
            }
        } catch (SearchIndexUnavailable) {
            $this->continueProjection((int) config('search.retry_delay'));
        }
    }

    private function continueProjection(int $delay = 0): void
    {
        $this->prependToChain(
            (new self($this->accountId, $this->revision, $this->modelClass, $this->modelId))
                ->onConnection('search-index')->onQueue('search-index')->beforeCommit()->delay($delay)
        );
    }
}
