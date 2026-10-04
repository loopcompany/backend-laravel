<?php

namespace Tests\Concerns;

use App\Models\Order;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * ساخت سریع کاربر و تکنسین معتبر برای تست‌ها (پروژه factory برای تکنسین ندارد).
 */
trait MakesPeople
{
    protected function makeTechnician(array $overrides = []): Technician
    {
        static $seq = 0;
        $seq++;

        return Technician::create(array_merge([
            'name' => "تکنسین {$seq}",
            'phone' => '0912' . str_pad((string) (1000000 + $seq), 7, '0', STR_PAD_LEFT),
            'father_name' => 'پدر',
            'issued_from' => 'تهران',
            'serial_number' => (string) $seq,
            'marital_status' => 'مجرد',
            'military_status' => 'اتمام خدمت',
            'education_status' => 'کارشناسی',
            'education_field' => 'کامپیوتر',
            'telephone' => '02100000000',
            'licence_date' => '2030-01-01',
            'vehicle_type' => 'موتور',
            'home_postal_code' => '1234567890',
            'region' => '1',
            'city' => 'تهران',
            'home_address' => 'آدرس',
            'idea' => '-',
            'software_skill' => '-',
            'hardware_skill' => '-',
            'software_weakness' => '-',
            'hardware_weakness' => '-',
            'referral_code' => (string) (500000 + $seq),
            'password' => Hash::make('secret123'),
            'has_access' => true,
            'approval_status' => Technician::APPROVAL_APPROVED,
        ], $this->hashPassword($overrides)));
    }

    protected function makeUser(array $overrides = []): User
    {
        static $seq = 0;
        $seq++;

        // forceFill: phone_verified_at و ... در $fillable مدل User نیستند
        $user = new User();
        $user->forceFill(array_merge([
            'name' => "کاربر {$seq}",
            'phone' => '0935' . str_pad((string) (1000000 + $seq), 7, '0', STR_PAD_LEFT),
            'code' => 'U' . $seq . uniqid(),
            'account_type' => 'individual',
            'password' => Hash::make('secret123'),
            'phone_verified_at' => now(),
        ], $this->hashPassword($overrides)))->save();

        return $user;
    }

    private function hashPassword(array $overrides): array
    {
        if (isset($overrides['password']) && !str_starts_with($overrides['password'], '$2y$')) {
            $overrides['password'] = Hash::make($overrides['password']);
        }

        return $overrides;
    }

    protected function makeCategory(string $title = 'تعمیر لپ‌تاپ'): int
    {
        return DB::table('categories')->insertGetId([
            'title' => $title,
            'image_path' => 'x.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeOrder(User $user, array $overrides = []): Order
    {
        $addressId = DB::table('user_addresses')->insertGetId([
            'user_id' => $user->id,
            'title' => 'خانه',
            'fname' => 'علی',
            'lname' => 'رضایی',
            'mobile' => '09120000000',
            'city' => 'تهران',
            'region' => '1',
            'address' => 'خیابان آزادی',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = new Order();
        $order->forceFill(array_merge([
            'user_id' => $user->id,
            'user_address_id' => $addressId,
            'category_id' => $overrides['category_id'] ?? $this->makeCategory(),
            'status' => 0,
        ], $overrides))->save();

        return $order->fresh();
    }
}
