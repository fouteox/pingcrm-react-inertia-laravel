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

    public function write(Contact|Organization|User $model, int $accountId, int $revision, bool $deleted = false, ?string $generation = null): void
    {
        if ($revision < 0 || $accountId < 1 || ! ctype_digit((string) $model->getKey())) {
            throw new InvalidArgumentException('Search documents require a positive account and model ID and a nonnegative revision.');
        }

        $document = $this->document($model, $accountId, $revision, $deleted);
        $documents = $this->client->getCollections()->{SearchGenerations::collection($model, $generation)}->getDocuments();

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
    public function staleIds(Contact|Organization|User $model, int $accountId, int $revision, int $limit, ?string $generation = null): array
    {
        if ($accountId < 1 || $revision < 0 || $limit < 1 || $limit > 250) {
            throw new InvalidArgumentException('Obsolete document searches require a positive account, a nonnegative revision, and a limit between 1 and 250.');
        }

        $results = $this->client->getCollections()->{SearchGenerations::collection($model, $generation)}->getDocuments()->search([
            'q' => '*',
            'filter_by' => sprintf(
                'account_id:=%d && search_deleted:!=true && (search_revision:<%d || search_revision:!=[0..%d])',
                $accountId, $revision, PHP_INT_MAX,
            ),
            'include_fields' => 'id',
            'per_page' => $limit,
            'page' => 1,
            'filter_curated_hits' => true,
            'enable_overrides' => false,
            'use_cache' => false,
        ]);
        $found = $results['found'] ?? null;
        $hits = $results['hits'] ?? null;

        if (($results['search_cutoff'] ?? false) !== false
            || ! is_int($found) || $found < 0
            || ! is_array($hits) || ! array_is_list($hits)
            || count($hits) > min($limit, $found)
            || ($found > 0 && $hits === [])) {
            throw new TypesenseClientError('Typesense did not return a complete obsolete document page.');
        }

        $ids = [];

        foreach ($hits as $hit) {
            $document = is_array($hit) ? ($hit['document'] ?? null) : null;
            $id = is_array($document) ? ($document['id'] ?? null) : null;
            $validatedId = is_string($id) && ctype_digit($id)
                ? filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                : false;

            if ($validatedId === false) {
                throw new TypesenseClientError('An indexed document has an invalid model ID.');
            }

            $ids[] = $validatedId;
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
