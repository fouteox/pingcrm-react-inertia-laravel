<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Typesense\Client;
use Typesense\Collection;
use Typesense\Exceptions\ObjectNotFound;

/**
 * @phpstan-type SearchField array{name: string, type: string, sort?: bool, optional?: bool}
 * @phpstan-type SearchSchema array{fields: list<SearchField>, default_sorting_field?: string}
 */
#[Signature('search:sync-schema')]
#[Description('Prepare Typesense name sorting and synchronously reindex existing records without deleting collections')]
final class SearchSyncSchemaCommand extends Command
{
    public function handle(Client $client): int
    {
        /** @var array<class-string<Contact|Organization|User>, array{collection-schema: SearchSchema}> $settings */
        $settings = Config::array('scout.typesense.model-settings');

        foreach ($settings as $modelClass => $configuration) {
            $index = (new $modelClass)->indexableAs();
            $schema = $configuration['collection-schema'];

            try {
                $this->prepareSorting($client->getCollections()->{$index}, $schema);
            } catch (ObjectNotFound) {
                $client->getCollections()->create(['name' => $index, ...$schema]);
            }

            $this->info("Prepared [$index].");
        }

        $previousDriver = config('scout.driver');
        $previousQueue = config('scout.queue');
        Config::set(['scout.driver' => 'typesense', 'scout.queue' => false]);

        try {
            foreach (array_keys($settings) as $modelClass) {
                $modelClass::makeAllSearchable();
                $this->info('Reindexed ['.(new $modelClass)->indexableAs().'].');
            }
        } finally {
            Config::set(['scout.driver' => $previousDriver, 'scout.queue' => $previousQueue]);
        }

        return self::SUCCESS;
    }

    /**
     * @param  SearchSchema  $schema
     */
    private function prepareSorting(Collection $collection, array $schema): void
    {
        /** @var list<SearchField> $existingFields */
        $existingFields = $collection->retrieve()['fields'];
        $existing = array_column($existingFields, null, 'name');
        $changes = [];

        foreach ($schema['fields'] as $field) {
            if ($field['name'] !== 'sort_id' && ! ($field['sort'] ?? false)) {
                continue;
            }

            $current = $existing[$field['name']] ?? null;

            if ($current === null) {
                $changes[] = $field;
            } elseif (($field['sort'] ?? false) && ! ($current['sort'] ?? false)) {
                $changes[] = ['name' => $field['name'], 'drop' => true];
                $changes[] = [...$current, ...$field];
            }
        }

        if ($changes !== []) {
            $collection->update(['fields' => $changes]);
        }
    }
}
