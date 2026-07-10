<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'May Usaris',
            'email' => 'user@example.com',
            'password' => bcrypt('test'),
            'is_admin' => false,
            'role_label' => RoleLabel::user,
        ]);

        User::factory()->create([
            'name' => 'Clair Wright',
            'email' => 'clair@example.com',
            'password' => bcrypt('test'),
            'is_admin' => false,
            'role_label' => RoleLabel::writer,
        ]);

        User::factory()->create([
            'name' => 'John Admantis',
            'email' => 'admin@example.com',
            'password' => bcrypt('test'),
            'is_admin' => true,
            'role_label' => RoleLabel::admin,
        ]);

        $this->call(BlogSeeder::class);
    }
}
