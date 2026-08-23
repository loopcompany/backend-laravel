<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpertisesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $items = [
            'کاربر سخت افزار',
            'کاربر نرم افزار',
            'کاربر شبکه',
            'کاربر پرینتر / کپی صنعتی',
            'کاربر جامع',
            'کاربر هارد دیسک',
            'کاربر دوربین مداربسته',
        ];

        foreach ($items as $title) {
            DB::table('expertises')->updateOrInsert(
                ['title' => $title],
                [
                    'title' => $title,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
