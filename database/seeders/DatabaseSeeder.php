<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create admin user for Filament.
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@localhost',
        ]);

        // Create 100 random Articles with random User (according ArticleFactory).
        Article::factory(100)->create();
    }
}
