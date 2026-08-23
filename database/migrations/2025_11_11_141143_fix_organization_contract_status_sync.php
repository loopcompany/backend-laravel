<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Organization;
use App\Models\OrganizationContract;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // اصلاح وضعیت قراردادهای موجود
        $organizations = Organization::where('contract_status', 'not_uploaded')->get();
        
        foreach ($organizations as $organization) {
            // بررسی آخرین قرارداد آپلود شده این سازمان
            $latestContract = OrganizationContract::latestForOrganization($organization->id)->first();
            
            if ($latestContract) {
                // اگر قراردادی آپلود شده، وضعیت سازمان را با وضعیت قرارداد همسان کن
                $organization->update([
                    'contract_status' => $latestContract->status,
                    'contract_approved_at' => $latestContract->status === 'approved' ? $latestContract->reviewed_at : null,
                ]);
                
                echo "Updated organization {$organization->id} contract status to {$latestContract->status}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // این migration فقط داده‌ها را اصلاح می‌کند، نیازی به rollback نیست
    }
};
