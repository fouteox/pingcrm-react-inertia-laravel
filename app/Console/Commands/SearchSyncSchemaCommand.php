<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchGenerations;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Typesense\Client;
use Typesense\Collection;
use Typesense\Exceptions\ObjectNotFound;

/**
 * @phpstan-type SearchField array{name: string, type: string, sort?: bool, optional?: bool}
 * @phpstan-type SearchSchema array{fields: list<SearchField>, default_sorting_field?: string}
 */
#[Signature('search:sync-schema')]
#[Description('Prepare Typesense name sorting and search revision fields without deleting collections')]
final class SearchSyncSchemaCommand extends Command
{
    public function handle(Client $client, SearchGenerations $generations): int
    {
        /** @var array<class-string<Contact|Organization|User>, array{collection-schema: SearchSchema}> $settings */
        $settings = Config::array('scout.typesense.model-settings');
        $generation = $generations->activeGeneration();

        foreach ($settings as $modelClass => $configuration) {
            $index = SearchGenerations::collection(new $modelClass, $generation);
            $schema = $configuration['collection-schema'];

            try {
                $this->prepareSchema($client->getCollections()->{$index}, $schema);
            } catch (ObjectNotFound) {
                Account::query()->update([
                    'indexed_revision' => null,
                    'search_revision' => DB::raw('search_revision + 1'),
                ]);
                $client->getCollections()->create(['name' => $index, ...$schema]);
            }

            $this->info("Prepared [$index].");
        }

        return self::SUCCESS;
    }

    /**
     * @param  SearchSchema  $schema
     */
    private function prepareSchema(Collection $collection, array $schema): void
    {
        /** @var list<SearchField> $existingFields */
        $existingFields = $collection->retrieve()['fields'];
        $existing = array_column($existingFields, null, 'name');
        $changes = [];

        foreach ($schema['fields'] as $field) {
            if ($field['name'] === 'id') {
                continue;
            }

            $current = $existing[$field['name']] ?? null;

            if ($current === null) {
                $changes[] = $field;
            } elseif (($field['sort'] ?? false) && ! ($current['sort'] ?? false)) {
                $changes[] = ['name' => $field['name'], 'drop' => true];
                $changes[] = [...$current, 'sort' => true];
            }
        }

        if ($changes !== []) {
            $collection->update(['fields' => $changes]);
        }
    }
}
