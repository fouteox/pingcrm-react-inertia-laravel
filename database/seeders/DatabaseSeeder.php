<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $account = Account::create(['name' => 'Acme Corporation']);

        User::firstOrCreate(
            ['email' => 'johndoe@example.com'],
            [
                'account_id' => $account->id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => Hash::make('secret'),
                'owner' => true,
                'email_verified_at' => now(),
            ]
        );

        User::factory(5)->create(['account_id' => $account->id]);

        $organizations = Organization::factory(100)
            ->create(['account_id' => $account->id]);

        Contact::factory(100)
            ->create(['account_id' => $account->id])
            ->each(function ($contact) use ($organizations) {
                $contact->update(['organization_id' => $organizations->random()->id]);
            });
    }
}
