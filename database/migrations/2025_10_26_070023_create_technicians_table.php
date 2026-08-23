<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('melicode')->nullable();
            $table->string('phone')->unique();
            $table->date('birth_date')->nullable()->comment('تاریخ تولد به شمسی');
            $table->string('father_name')->comment('نام پدر');
            $table->string('issued_from')->comment('صادره از');
            $table->string('serial_number')->comment('شماره شناسنامه');
            $table->enum('marital_status', ['مجرد', 'متأهل'])->comment('وضعیت تاهل');
            $table->enum('military_status', ['معاف', 'در حال خدمت', 'پایان خدمت'])->comment('وضعیت نظام وظیفه');
            $table->string('education_status')->comment('وضعیت تحصیلات');
            $table->string('telephone')->comment('شماره تلفن ثابت');
            $table->string('mobile')->comment('شماره تلفن همراه')->nullable();
            $table->string('id_card_number')->nullable()->comment('شماره کارت شناسایی');
            $table->date('licence_date')->comment('تاریخ اعتبار گواهینامه');
            $table->string('vehicle_type')->comment('نوع وسیله نقلیه');
            $table->string('home_postal_code')->comment('کد پستی منزل');
            $table->string('region')->comment('منطقه');
            $table->string('city')->comment('شهر');
            $table->string('home_address')->comment('آدرس منزل');

            $table->timestamp('phone_verified_at')->nullable();
            $table->string('phone_verify_code')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('profile_photo_path')->nullable();

            $table->string('referral_code')->nullable()->comment('کد پرسنلی');
            $table->string('other_referral_code')->nullable()->comment('کد پرسنلی دیگران');
            $table->string('password')->nullable();
            $table->tinyInteger('has_access')->default(1)->comment('0: no/1: yes');


            $table->string('idea')->comment('ایده/ خلاقیت');
            $table->string('software_skill')->comment('تسلط / توانایی ها نرم افزار');
            $table->string('hardware_skill')->comment('تسلط / توانایی ها سخت افزار');
            $table->string('software_weakness')->comment('ناآگاهی / نقاط ضعف نرم افزار');
            $table->string('hardware_weakness')->comment('ناآگاهی / نقاط ضعف سخت افزار');
            $table->string('resume')->nullable()->comment('رزومه');



            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
};
