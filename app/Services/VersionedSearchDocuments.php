<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Typesense\Client;
use Typesense\Documents;
use Typesense\Exceptions\ObjectAlreadyExists;
use Typesense\Exceptions\TypesenseClientError;

final class VersionedSearchDocuments
{
    public function __construct(private readonly Client $client) {}

    public function write(Contact|Organization|User $model, int $accountId, int $revision, bool $deleted = false): void
    {
        if ($revision < 0 || $accountId < 1 || ! ctype_digit((string) $model->getKey())) {
            throw new InvalidArgumentException('Search documents require a positive account and model ID and a nonnegative revision.');
        }

        $document = $this->document($model, $accountId, $revision, $deleted);
        $documents = $this->client->getCollections()->{$model->indexableAs()}->getDocuments();

        if ($this->create($documents, $document)) {
            return;
        }

        $result = $documents->update($document, [
            'filter_by' => sprintf(
                'id:=%s && (search_revision:<%d || search_revision:!=[0..%d])',
                $document['id'], $revision, PHP_INT_MAX
            ),
        ]);

        if (($result['num_updated'] ?? 0) === 1) {
            return;
        }

        $current = $documents[$document['id']]->retrieve();
        $currentRevision = $current['search_revision'] ?? -1;

        if (($current['account_id'] ?? null) !== $accountId || $currentRevision < $revision) {
            throw new TypesenseClientError('Typesense did not acknowledge the requested search revision.');
        }
    }

    /** @return list<int> */
    public function ids(Contact|Organization|User $model, int $accountId): array
    {
        $export = $this->client->getCollections()->{$model->indexableAs()}->getDocuments()
            ->export(['filter_by' => 'account_id:='.$accountId.' && search_deleted:!=true', 'include_fields' => 'id']);

        $ids = [];

        foreach (preg_split('/\R/', mb_trim($export), flags: PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
            $document = json_decode($line, true, flags: JSON_THROW_ON_ERROR);

            if (! ctype_digit((string) ($document['id'] ?? ''))) {
                throw new TypesenseClientError('An indexed document has an invalid model ID.');
            }

            $ids[] = (int) $document['id'];
        }

        return $ids;
    }

    /** @param array<string, mixed> $document */
    private function create(Documents $documents, array $document): bool
    {
        try {
            $documents->create($document);

            return true;
        } catch (ObjectAlreadyExists) {
            return false;
        }
    }

    /** @return array<string, mixed> */
    private function document(Contact|Organization|User $model, int $accountId, int $revision, bool $deleted): array
    {
        $document = $deleted ? [] : $model->toSearchableArray();

        foreach (Config::array('scout.typesense.model-settings.'.$model::class.'.collection-schema.fields') as $field) {
            if (! ($field['optional'] ?? false)) {
                $document[$field['name']] ??= match ($field['type']) {
                    'int32', 'int64' => 0,
                    'bool' => false,
                    default => '',
                };
            }
        }

        return [
            ...$document,
            'id' => (string) $model->getKey(),
            'account_id' => $accountId,
            '__soft_deleted' => (int) ($deleted || $model->getAttribute('deleted_at') !== null),
            'search_revision' => $revision,
            'search_deleted' => $deleted,
        ];
    }
}
