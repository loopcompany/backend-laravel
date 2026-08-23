<?php

namespace Database\Seeders;

use App\Models\LetterRate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LetterRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $letterRates = [
            // Union Letter Rates
            [
                'type' => 'union',
                'title' => 'نامه عادی',
                'amount' => '5,000 تومان',
            ],
            [
                'type' => 'union',
                'title' => 'نامه سفارشی',
                'amount' => '8,000 تومان',
            ],
            [
                'type' => 'union',
                'title' => 'پیک موتوری',
                'amount' => '15,000 تومان',
            ],
            [
                'type' => 'union',
                'title' => 'ارسال فوری',
                'amount' => '25,000 تومان',
            ],

            // Loop Letter Rates
            [
                'type' => 'loop',
                'title' => 'ارسال استاندارد لوپ',
                'amount' => '7,000 تومان',
            ],
            [
                'type' => 'loop',
                'title' => 'ارسال سریع لوپ',
                'amount' => '12,000 تومان',
            ],
            [
                'type' => 'loop',
                'title' => 'ارسال ویژه لوپ',
                'amount' => '18,000 تومان',
            ],
            [
                'type' => 'loop',
                'title' => 'ارسال فوری لوپ',
                'amount' => '30,000 تومان',
            ],
        ];

        foreach ($letterRates as $rate) {
            LetterRate::create($rate);
        }
    }
}
