<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->string('technician_type')->nullable()->after('name')->comment('مثلا تکنسین جامع میدانی');
            $table->string('certificate_number')->nullable()->after('technician_type')->comment('شماره گواهینامه');
            $table->string('certificate_issue_date')->nullable()->after('certificate_number')->comment('تاریخ صدور گواهینامه');
            $table->string('car_model')->nullable()->after('certificate_issue_date')->comment('مدل ماشین');
            $table->string('car_color')->nullable()->after('car_model')->comment('رنگ ماشین');
            $table->string('car_plate')->nullable()->after('car_color')->comment('پلاک');
            $table->string('car_year')->nullable()->after('car_plate')->comment('سال ساخت ماشین');
            $table->string('car_fuel_type')->nullable()->after('car_year')->comment('نوع سوخت ماشین');
            $table->string('car_vin')->nullable()->after('car_fuel_type')->comment('شماره شناسه وسیله(VIN)');
            $table->string('car_insurance_code')->nullable()->after('car_vin')->comment('کد یکتای بیمه شخص ثالث');
            $table->string('car_insurance_expiry_date')->nullable()->after('car_insurance_code')->comment('تاریخ انقضاء بیمه شخص ثالث');
            $table->string('bank_shaba_number')->nullable()->after('car_insurance_expiry_date')->comment('شماره شبا بانک');
            $table->string('bank_name')->nullable()->after('bank_shaba_number')->comment('بانک');
            $table->string('bank_card_number')->nullable()->after('bank_name')->comment('شماره کارت');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->dropColumn('technician_type');
        });
    }
};
