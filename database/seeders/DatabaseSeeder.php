<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use UnexpectedValueException;

final class DatabaseSeeder extends Seeder
{
    public const string DEMO_ACCOUNT_NAME = 'Acme Corporation';

    public const string DEMO_ACCOUNT_KEY = 'primary';

    public const string DEMO_USER_EMAIL = 'johndoe@example.com';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $account = Account::where('demo_key', self::DEMO_ACCOUNT_KEY)->first();
        $demoUser = User::withTrashed()
            ->where('email', self::DEMO_USER_EMAIL)
            ->first();

        if ($account === null && $demoUser !== null) {
            throw new UnexpectedValueException(
                'The canonical demo email exists without a marked demo account.'
            );
        }

        if ($account !== null && $demoUser !== null && $demoUser->account_id !== $account->id) {
            throw new UnexpectedValueException(
                'The canonical demo email belongs to an account other than the marked demo account.'
            );
        }

        $account ??= Account::create(['name' => self::DEMO_ACCOUNT_NAME]);

        $account->forceFill([
            'name' => self::DEMO_ACCOUNT_NAME,
            'demo_key' => self::DEMO_ACCOUNT_KEY,
        ])->save();

        $password = $demoUser !== null && Hash::check('secret', $demoUser->password)
            ? $demoUser->password
            : Hash::make('secret');

        $demoUser ??= new User;
        $demoUser->forceFill([
            'account_id' => $account->id,
            'email' => self::DEMO_USER_EMAIL,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => $password,
            'owner' => true,
            'email_verified_at' => now(),
            'deleted_at' => null,
        ])->save();

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
