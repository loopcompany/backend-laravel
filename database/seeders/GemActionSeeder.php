<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GemAction;

class GemActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gemActions = [
            [
                'name' => '🌟',
                'action_key' => 'gem_10',
                'gems' => 10,
                'is_active' => true,
            ],
            [
                'name' => '💎',
                'action_key' => 'gem_25',
                'gems' => 25,
                'is_active' => true,
            ],
            [
                'name' => '🎯',
                'action_key' => 'gem_50',
                'gems' => 50,
                'is_active' => true,
            ],
            [
                'name' => '⭐',
                'action_key' => 'gem_75',
                'gems' => 75,
                'is_active' => true,
            ],
            [
                'name' => '🏆',
                'action_key' => 'gem_100',
                'gems' => 100,
                'is_active' => true,
            ],
            [
                'name' => '🎪',
                'action_key' => 'gem_150',
                'gems' => 150,
                'is_active' => true,
            ],
            [
                'name' => '🎨',
                'action_key' => 'gem_250',
                'gems' => 250,
                'is_active' => true,
            ],
            [
                'name' => '🌟',
                'action_key' => 'gem_jackpot',
                'gems' => 500,
                'is_active' => true,
            ],
        ];

        foreach ($gemActions as $action) {
            GemAction::create($action);
        }
    }
}
