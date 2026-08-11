<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Console\Commands\ResetDemoCommand;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Laravel\Scout\Engines\TypesenseEngine;

#[Tries(12)]
#[Backoff(5, 15, 30, 60, 120, 300)]
#[Timeout(60)]
final class ReconcileDemoSearchIndex implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $accountId) {}

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(ResetDemoCommand::LOCK_KEY))
                ->shared()
                ->releaseAfter(60)
                ->expireAfter((int) config('demo.reset.lock_seconds')),
        ];
    }

    public function handle(): void
    {
        $this->reconcile(Contact::class);
        $this->reconcile(Organization::class);
        $this->reconcile(User::class);
    }

    /**
     * @param  class-string<Contact|Organization|User>  $modelClass
     */
    private function reconcile(string $modelClass): void
    {
        $model = new $modelClass;
        $query = $modelClass::query()->where('account_id', $this->accountId);

        if ($modelClass === Contact::class) {
            $query->with('organization');
        }

        /** @var Collection<int, Contact|Organization|User> $models */
        $models = $query->get();
        $models->searchableSync();

        $engine = $model->searchableUsing();

        if (! $engine instanceof TypesenseEngine) {
            return;
        }

        $documents = $engine
            ->getCollections()
            ->{$model->indexableAs()}
            ->getDocuments()
            ->export(['filter_by' => 'account_id:='.$this->accountId]);

        $indexedIds = collect(preg_split('/\R/', mb_trim($documents)) ?: [])
            ->filter()
            ->map(function (string $document): string {
                /** @var array{id: int|string} $decoded */
                $decoded = json_decode($document, true, flags: JSON_THROW_ON_ERROR);

                return (string) $decoded['id'];
            });

        // Read the database again after the export so concurrent creations are
        // never classified as stale and concurrent deletions cannot be reintroduced.
        $currentIds = $modelClass::query()
            ->where('account_id', $this->accountId)
            ->pluck($model->getKeyName())
            ->map(fn (int|string $id): string => (string) $id);

        $indexedIds
            ->diff($currentIds)
            ->chunk(100)
            ->each(function ($staleIds) use ($engine, $model): void {
                $filter = sprintf(
                    'account_id:=%d && id:[%s]',
                    $this->accountId,
                    $staleIds->implode(',')
                );

                $engine
                    ->getCollections()
                    ->{$model->indexableAs()}
                    ->getDocuments()
                    ->delete(['filter_by' => $filter]);
            });
    }
}
