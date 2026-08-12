<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Console\Commands\ResetDemoCommand;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Laravel\Scout\Engines\TypesenseEngine;
use RuntimeException;

#[Tries(12)]
#[Backoff(5, 15, 30, 60, 120, 300)]
#[Timeout(60)]
final class ReconcileDemoSearchIndex implements ShouldQueue
{
    use Queueable;

    private const int MAX_CONVERGENCE_PASSES = 3;

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
        $this->indexStableSnapshot($modelClass, $model);

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

        // Read IDs after the export so a concurrently indexed creation is never
        // mistaken for a stale document.
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

    /**
     * @param  class-string<Contact|Organization|User>  $modelClass
     * @param  Contact|Organization|User  $model
     */
    private function indexStableSnapshot(string $modelClass, Model $model): void
    {
        for ($pass = 1; $pass <= self::MAX_CONVERGENCE_PASSES; $pass++) {
            $models = $this->loadModels($modelClass, $model);
            $fingerprints = $this->searchFingerprints($models);

            $models->searchableSync();

            $currentModels = $this->loadModels($modelClass, $model);

            if ($fingerprints === $this->searchFingerprints($currentModels)) {
                return;
            }
        }

        throw new RuntimeException(sprintf(
            'Search data for account %d kept changing during reconciliation.',
            $this->accountId
        ));
    }

    /**
     * @param  class-string<Contact|Organization|User>  $modelClass
     * @param  Contact|Organization|User  $model
     * @return Collection<int, Contact|Organization|User>
     */
    private function loadModels(string $modelClass, Model $model): Collection
    {
        $query = $modelClass::query()->where('account_id', $this->accountId);

        if ($modelClass === Contact::class) {
            $query->with('organization');
        }

        /** @var Collection<int, Contact|Organization|User> $models */
        $models = $query->orderBy($model->getKeyName())->get();

        return $models;
    }

    /**
     * @param  Collection<int, Contact|Organization|User>  $models
     * @return array<int|string, string>
     */
    private function searchFingerprints(Collection $models): array
    {
        return $models
            ->mapWithKeys(fn (Model $model): array => [
                $model->getKey() => hash(
                    'sha256',
                    json_encode($model->toSearchableArray(), JSON_THROW_ON_ERROR)
                ),
            ])
            ->all();
    }
}
