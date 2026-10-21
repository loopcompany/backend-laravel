<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * سند «فرآیند ثبت، بررسی، قیمت‌گذاری و ارجاع سفارش» (پنل سفارشات):
 * - orders.workflow_stage: مرحله‌ی فعلی سفارش در فرآیند پنل
 * - order_workflows: جزئیات هر مرحله (بررسی، تخصیص، نتیجه‌ی مراجعه، انجام در محل، ارجاع، لغو، پایان)
 * - order_quotes: نسخه‌های قیمت (اولیه، اصلاح‌شده، نهایی) — بند ۷-۸ و ۱۲-۲
 * - order_stage_logs: تاریخچه‌ی تغییر وضعیت با مسئول، زمان و علت — بند ۱۲-۴
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('workflow_stage', 32)->nullable()->after('status')->index();
        });

        Schema::create('order_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();

            // ۳ — بررسی ادمین
            $table->string('service_type', 32)->nullable();
            $table->string('customer_type', 32)->nullable();
            $table->string('requester_name')->nullable();
            $table->string('requester_phone', 32)->nullable();
            $table->string('contract_reference')->nullable();
            $table->string('review_status', 16)->default('pending');
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('route_to_loop_reason')->nullable();

            // ۵ — تخصیص تکنسین
            $table->string('assignment_status', 16)->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('assignment_responded_at')->nullable();
            $table->text('assignment_note')->nullable();
            $table->text('assignment_reject_reason')->nullable();

            // ۶ — نتیجه‌ی بررسی در محل
            $table->string('visit_outcome', 32)->nullable();
            $table->text('visit_outcome_note')->nullable();
            $table->timestamp('visit_outcome_at')->nullable();

            // ۸ — انجام خدمت در محل
            $table->text('service_actions')->nullable();
            $table->text('service_parts_used')->nullable();
            $table->text('service_result')->nullable();

            // ۹ — ارجاع دستگاه به شرکت
            $table->string('transfer_method', 32)->nullable();
            $table->text('transfer_reason')->nullable();
            $table->text('transfer_device_condition')->nullable();
            $table->text('transfer_accessories')->nullable();
            $table->text('transfer_courier_info')->nullable();
            $table->timestamp('transfer_started_at')->nullable();
            $table->string('transfer_received_by')->nullable();
            $table->timestamp('transfer_received_at')->nullable();
            $table->string('transfer_receipt_number', 64)->nullable();
            $table->text('transfer_custody_note')->nullable();

            // ۱۰ — لغو سفارش
            $table->string('cancel_reason_type', 32)->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('cancel_financial_note')->nullable();
            $table->boolean('device_return_required')->default(false);

            // ۱۱ — پایان سفارش و نتیجه
            $table->string('field_result', 40)->nullable();
            $table->text('closure_note')->nullable();
            $table->timestamp('field_closed_at')->nullable();

            $table->timestamps();
        });

        Schema::create('order_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('kind', 16)->comment('initial | revised | final');
            $table->unsignedBigInteger('service_fee')->default(0);
            $table->unsignedBigInteger('parts_fee')->default(0);
            $table->unsignedBigInteger('extra_fee')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->boolean('is_estimate')->default(true);
            $table->string('estimated_duration')->nullable();
            $table->date('visit_date')->nullable();
            $table->string('visit_time', 32)->nullable();
            $table->text('parts_description')->nullable();
            $table->text('change_reason')->nullable();
            $table->text('description')->nullable();
            $table->boolean('requires_user_approval')->default(true);
            $table->string('status', 24)->comment('pending_review | sent | accepted | rejected | superseded | discarded | recorded');
            $table->string('proposed_by_type', 16)->comment('admin | technician');
            $table->unsignedBigInteger('proposed_by_id')->nullable();
            $table->unsignedBigInteger('approved_by_admin_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('decided_by_type', 16)->nullable()->comment('user | admin (از طرف کاربر)');
            $table->unsignedBigInteger('decided_by_id')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'version']);
        });

        Schema::create('order_stage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_stage', 32)->nullable();
            $table->string('to_stage', 32)->nullable();
            $table->string('action', 48);
            $table->string('actor_type', 16)->comment('admin | user | technician | system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['order_id', 'id']);
        });

        // سفارش‌های قبلی: مرحله از روی فیلدهای موجود برآورد می‌شود
        DB::table('orders')->update([
            'workflow_stage' => DB::raw("CASE
                WHEN status IN (3, 4, 5, 6) THEN 'cancelled'
                WHEN status = 2 THEN 'closed'
                WHEN send_to_loop IS NOT NULL THEN 'transfer'
                WHEN done_in_place IS NOT NULL THEN 'onsite_service'
                WHEN technician_id IS NOT NULL THEN 'visit'
                ELSE 'review'
            END"),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_stage_logs');
        Schema::dropIfExists('order_quotes');
        Schema::dropIfExists('order_workflows');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['workflow_stage']);
            $table->dropColumn('workflow_stage');
        });
    }
};
