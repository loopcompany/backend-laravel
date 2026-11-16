<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->longText('des')->nullable()->change());
        Schema::table('orders', fn (Blueprint $table) => $table->json('delivery_modes')->nullable());
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->string('accounting_status', 20)->nullable()->index();
            $table->unsignedBigInteger('accounting_reviewed_by')->nullable();
            $table->timestamp('accounting_reviewed_at')->nullable();
            $table->text('accounting_review_note')->nullable();
        });
        DB::table('user_transactions')->where('status', 100)->where(function ($query) {
            $query->whereIn('type', [1, 2])->orWhereIn('payment_method', ['card_to_card', 'sheba', 'bank_transfer']);
        })->update(['accounting_status' => 'pending']);
        Schema::table('order_workflows', function (Blueprint $table) {
            $table->string('customer_visit_status', 24)->nullable();
            $table->timestamp('customer_visit_decided_at')->nullable();
            $table->text('customer_visit_note')->nullable();
            $table->unsignedBigInteger('manager_assigned_by')->nullable();
        });
        Schema::table('organization_contract_requests', function (Blueprint $table) {
            $table->json('delivery_modes')->nullable();
            $table->unsignedBigInteger('previous_request_id')->nullable();
            $table->timestamp('final_published_at')->nullable();
        });
        Schema::table('pdf_documents', function (Blueprint $table) {
            $table->string('organization_assessment')->nullable();
            $table->string('organization_agreement_draft')->nullable();
        });
        foreach (['review-order-payments', 'assign-paid-orders'] as $name) {
            $permission=\Spatie\Permission\Models\Permission::findOrCreate($name, 'admin');
            \Spatie\Permission\Models\Role::where('name','super-admin')->where('guard_name','admin')->each(fn ($role) => $role->givePermissionTo($permission));
        }
        foreach (['accountant'=>['view-orders','review-order-payments'], 'technician-manager'=>['view-orders','edit-orders','assign-paid-orders']] as $name=>$permissions) {
            $role=\Spatie\Permission\Models\Role::firstOrCreate(['name'=>$name,'guard_name'=>'admin']);
            foreach ($permissions as $permission) $role->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($permission,'admin'));
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('delivery_modes'));
        Schema::table('user_transactions', fn (Blueprint $table) => $table->dropColumn(['accounting_status','accounting_reviewed_by','accounting_reviewed_at','accounting_review_note']));
        Schema::table('order_workflows', fn (Blueprint $table) => $table->dropColumn(['customer_visit_status','customer_visit_decided_at','customer_visit_note','manager_assigned_by']));
        Schema::table('organization_contract_requests', fn (Blueprint $table) => $table->dropColumn(['delivery_modes','previous_request_id','final_published_at']));
        Schema::table('pdf_documents', fn (Blueprint $table) => $table->dropColumn(['organization_assessment','organization_agreement_draft']));
        // Keep the widened description column to avoid losing previously saved notes.
    }
};
