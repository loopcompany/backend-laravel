<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Organization;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FieldServiceDemo extends Command
{
    protected $signature = 'field-service:demo';
    protected $description = 'Create a local organization, field technician, and order for manual field-service testing';

    public function handle(): int
    {
        if (!app()->environment(['local', 'testing'])) {
            $this->error('This command only runs in local or testing environments.');

            return self::FAILURE;
        }

        $result = DB::transaction(function (): array {
            $suffix = (string) random_int(1000000, 9999999);
            $organizationUser = new User();
            $organizationUser->forceFill([
                'name' => 'مسئول سازمان تست',
                'phone' => '0992' . $suffix,
                'code' => 'DEMO-ORG-' . $suffix,
                'account_type' => 'company',
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'phone_verified_at' => now(),
            ])->saveQuietly();

            $organization = new Organization();
            $organization->forceFill([
                'user_id' => $organizationUser->id,
                'organization_name' => 'سازمان آزمایشی گزارش روزانه',
                'organization_code' => 'DEMO-' . $suffix,
                'organization_phone' => '02100000000',
                'organization_address' => 'تهران، ساختمان آزمایشی',
                'manager_full_name' => 'مسئول سازمان تست',
                'manager_national_code' => '30' . str_pad($suffix, 8, '0', STR_PAD_LEFT),
                'profile_status' => 'approved',
                'contract_status' => 'approved',
            ])->saveQuietly();

            $technician = new Technician();
            $technician->forceFill([
                'name' => 'تکنسین میدانی تست',
                'phone' => '0993' . $suffix,
                'father_name' => 'آزمایشی',
                'issued_from' => 'تهران',
                'serial_number' => $suffix,
                'marital_status' => 'مجرد',
                'military_status' => 'اتمام خدمت',
                'education_status' => 'کارشناسی',
                'education_field' => 'کامپیوتر',
                'telephone' => '02100000000',
                'licence_date' => '2030-01-01',
                'vehicle_type' => 'موتور',
                'home_postal_code' => '1234567890',
                'region' => '۱',
                'city' => 'تهران',
                'home_address' => 'آدرس آزمایشی',
                'idea' => '-',
                'software_skill' => '-',
                'hardware_skill' => '-',
                'software_weakness' => '-',
                'hardware_weakness' => '-',
                'referral_code' => 'DEMO-' . $suffix,
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'has_access' => true,
                'approval_status' => Technician::APPROVAL_APPROVED,
                'technician_type' => Technician::TYPE_FIELD,
            ])->saveQuietly();

            $categoryId = DB::table('categories')->value('id') ?? DB::table('categories')->insertGetId([
                'title' => 'تجهیزات آزمایشی', 'image_path' => 'demo.png',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $addressId = DB::table('user_addresses')->insertGetId([
                'user_id' => $organizationUser->id,
                'title' => 'ساختمان مرکزی', 'unit' => '۲', 'floor' => '۳', 'room' => '۳۰۵', 'number' => '۱۲',
                'fname' => 'مسئول', 'lname' => 'تست', 'mobile' => $organizationUser->phone,
                'city' => 'تهران', 'region' => '۱', 'address' => 'خیابان آزمایشی، ساختمان مرکزی',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $order = new Order();
            $order->forceFill([
                'user_id' => $organizationUser->id,
                'user_address_id' => $addressId,
                'category_id' => $categoryId,
                'technician_id' => $technician->id,
                'status' => 1,
                'des' => 'بررسی خطای دستگاه در اتاق ۳۰۵',
            ])->saveQuietly();

            return [
                'order_id' => $order->id,
                'organization_user_id' => $organizationUser->id,
                'technician_id' => $technician->id,
                'organization_token' => $organizationUser->createToken('field-service-demo')->plainTextToken,
                'technician_token' => $technician->createToken('field-service-demo')->plainTextToken,
            ];
        });

        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->warn('These local demo tokens are shown once. Do not use them on a production server.');

        return self::SUCCESS;
    }
}
