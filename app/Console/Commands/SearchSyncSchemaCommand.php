<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchGenerations;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Typesense\Client;
use Typesense\Collection;
use Typesense\Exceptions\ObjectAlreadyExists;
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
        $prepared = [];

        while (true) {
            $targets = array_filter($generations->schemaTargets(), fn (?string $generation): bool => ! isset($prepared[$generation ?? 'legacy']));

            if ($targets === []) {
                return self::SUCCESS;
            }

            foreach ($targets as $generation) {
                foreach ($settings as $modelClass => $configuration) {
                    $index = SearchGenerations::collection(new $modelClass, $generation);
                    $schema = $configuration['collection-schema'];

                    try {
                        $this->prepareSchema($client->getCollections()->{$index}, $schema);
                    } catch (ObjectNotFound) {
                        $status = $generations->invalidateMissingCollection($generation);

                        if ($status === 'retired') {
                            continue 2;
                        }

                        if ($status === 'abandoned') {
                            $this->error("Candidate [$generation] lost a collection and was retired; run search:rebuild --background to start a complete replacement.");

                            return self::FAILURE;
                        }

                        try {
                            $client->getCollections()->create(['name' => $index, ...$schema]);
                        } catch (ObjectAlreadyExists) {
                            $this->prepareSchema($client->getCollections()->{$index}, $schema);
                        }
                    }

                    $this->info("Prepared [$index].");
                }

                $prepared[$generation ?? 'legacy'] = true;
            }
        }
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
