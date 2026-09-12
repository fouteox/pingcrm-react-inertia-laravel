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
#[Description('Enable Typesense name sorting using the stored documents without deleting collections')]
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
            if (! ($field['sort'] ?? false)) {
                continue;
            }

            $current = $existing[$field['name']] ?? null;

            if ($current === null) {
                $changes[] = $field;
            } elseif (! ($current['sort'] ?? false)) {
                $changes[] = ['name' => $field['name'], 'drop' => true];
                $changes[] = [...$current, 'sort' => true];
            }
        }

        if ($changes !== []) {
            $collection->update(['fields' => $changes]);
        }
    }
}
