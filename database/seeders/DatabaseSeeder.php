<?php

namespace Database\Seeders;

use App\Enums\PowerXRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PowerXAccessSeeder::class);

        $user = User::query()
            ->where('email', 'test@example.com')
            ->first();

        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $user->assignRole(PowerXRole::Management->value);

        if (! app()->isProduction()) {
            $this->call(PowerXDemoSeeder::class);
        }
    }
}
