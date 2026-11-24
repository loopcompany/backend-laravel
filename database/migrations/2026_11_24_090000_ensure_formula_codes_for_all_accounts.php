<?php

use App\Models\Admin;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/*
 * کد فرمول برای همه‌ی حساب‌ها — درخواست کارفرما (۲۵ آبان ۱۴۰۵، مورد ۱۸):
 * «شماره‌ی کاربری فقط رقم باشد، بدون خط تیره و نقطه» و «همه‌ی کاربرها، چه تکنسین و چه کاربر و چه ادمین،
 * باید این کد را داشته باشند».
 *
 * چرا لازم شد:
 *  - migration «normalize_registration_code_separators» (۲۶ آبان) قبل از اصلاح مولد اجرا شد؛ کدهایی که
 *    تا اصلاح مولد صادر شدند هنوز «-» و «.» دارند و چون isIssued() آن‌ها را معتبر می‌شمارد هرگز عوض نمی‌شوند.
 *  - کدهای قبل از فرمول (۸ تا ۱۲ رقم) و حساب‌های بدون کد هرگز کد فرمول نگرفتند.
 *  - کد مدیران متن دلخواهِ واردشده در پنل بود.
 *
 * هر تغییر (مقدار قدیم → تازه) در registration_code_format_changes با `reason` ثبت می‌شود و down()
 * برمی‌گرداند. اجرای تکراری بی‌اثر است. پیامک ارسال نمی‌شود: کد پرسنلی تکنسین کد ورود اوست و ادمین
 * باید با «ارسال مجدد مشخصات» کد تازه را به او برساند.
 */
return new class extends Migration
{
    private const CODES = [
        'users' => 'code',
        'employment_files' => 'personnel_code',
        'technicians' => 'referral_code',
        'admins' => 'personnel_code',
        'organizations' => 'organization_code',
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('registration_code_format_changes', 'reason')) {
            Schema::table('registration_code_format_changes', function (Blueprint $table): void {
                $table->string('reason', 32)->nullable()->after('new_value');
            });
        }

        $service = app(\App\Services\RegistrationCodeService::class);
        $summary = ['separators' => 0, 'users' => 0, 'technicians' => 0, 'admins' => 0, 'skipped' => []];

        DB::transaction(function () use ($service, &$summary): void {
            // ۱) جداکننده‌ها را از هر کدی که فقط از رقم و «-» و «.» ساخته شده برمی‌داریم.
            foreach (self::CODES as $table => $field) {
                DB::table($table)->whereNotNull($field)->orderBy('id')->chunkById(200, function ($rows) use ($table, $field, &$summary): void {
                    foreach ($rows as $row) {
                        $digits = str_replace(['-', '.'], '', (string) $row->$field);
                        if ($digits === $row->$field || !preg_match('/^[0-9]+$/D', $digits)) {
                            continue;
                        }
                        $this->apply($table, $row->id, $field, $row->$field, $digits, 'separators');
                        $summary['separators']++;
                    }
                });
            }

            // ۲) حساب‌هایی که کدِ فرمول ندارند کد تازه می‌گیرند.
            foreach (User::orderBy('id')->lazyById(200) as $user) {
                if ($service::isIssued($user->code)) {
                    continue;
                }
                $data = $service->legacyIssueData($user);
                if (!$data) {
                    $summary['skipped']['users_without_location'] = ($summary['skipped']['users_without_location'] ?? 0) + 1;
                    continue;
                }
                $this->reissue($service, $user, 'users', 'code', $data, $summary);
            }

            // تکنسین در زمان تأیید ادمین کد می‌گیرد؛ پس فقط تکنسین‌های تأییدشده این‌جا کد فرمول می‌گیرند.
            foreach (Technician::where('approval_status', Technician::APPROVAL_APPROVED)->orderBy('id')->lazyById(200) as $technician) {
                if ($service::isIssued($technician->referral_code)) {
                    continue;
                }
                $data = $service->legacyIssueData($technician);
                if (!$data) {
                    $summary['skipped']['technicians_without_location'] = ($summary['skipped']['technicians_without_location'] ?? 0) + 1;
                    continue;
                }
                $this->reissue($service, $technician, 'technicians', 'referral_code', $data, $summary);
            }

            foreach (Admin::orderBy('id')->lazyById(200) as $admin) {
                if ($service::isIssued($admin->personnel_code)) continue;
                $city = (string) $admin->registration_city_code;
                $region = (string) $admin->registration_region_code;
                $method = (int) $admin->registration_method;
                if (!preg_match('/^[0-9]{2}$/D', $city) || !preg_match('/^[0-9]{2}$/D', $region) || !isset($service::METHODS[$method])) {
                    $summary['skipped']['admins_without_origin'] = ($summary['skipped']['admins_without_origin'] ?? 0) + 1;
                    continue;
                }
                $this->reissue($service, $admin, 'admins', 'personnel_code', ['city_code' => $city, 'region_code' => $region, 'method' => $method], $summary);
            }
        });

        Log::info('Registration codes: separators removed and formula codes issued for every account.', $summary);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (DB::table('registration_code_format_changes')->whereNotNull('reason')->orderByDesc('id')->get() as $change) {
                if (!Schema::hasTable($change->table_name) || !Schema::hasColumn($change->table_name, $change->field_name)) {
                    continue;
                }
                DB::table($change->table_name)->where('id', $change->record_id)->where($change->field_name, $change->new_value)
                    ->update([$change->field_name => $change->old_value === '' ? null : $change->old_value]);
            }
            DB::table('registration_code_format_changes')->whereNotNull('reason')->delete();
        });
        if (Schema::hasColumn('registration_code_format_changes', 'reason')) {
            Schema::table('registration_code_format_changes', fn (Blueprint $table) => $table->dropColumn('reason'));
        }
    }

    /** یک کد را عوض می‌کند و تغییر را ثبت می‌کند؛ برخورد با کدِ حساب دیگر کل migration را متوقف می‌کند. */
    private function apply(string $table, int $id, string $field, ?string $old, string $new, string $reason): void
    {
        if (DB::table($table)->where($field, $new)->where('id', '!=', $id)->exists()) {
            throw new RuntimeException("Registration code collision on {$table}.{$field} for record {$id}; migration aborted.");
        }
        DB::table('registration_code_format_changes')->insert([
            'table_name' => $table, 'record_id' => $id, 'field_name' => $field,
            'old_value' => (string) $old, 'new_value' => $new, 'reason' => $reason,
        ]);
        DB::table($table)->where('id', $id)->update([$field => $new]);
    }

    private function reissue(\App\Services\RegistrationCodeService $service, Admin|User|Technician $record, string $table, string $field, array $data, array &$summary): void
    {
        $old = $record->$field;
        $fields = ['registration_method', 'registration_city_code', 'registration_region_code'];
        $previous = $record->only($fields);
        $new = $service->issue($record, $data);
        $changed = $record->fresh()->only($fields);
        foreach ($fields as $key) {
            if ((string) $previous[$key] === (string) $changed[$key]) continue;
            DB::table('registration_code_format_changes')->insert([
                'table_name' => $table, 'record_id' => $record->getKey(), 'field_name' => $key,
                'old_value' => (string) $previous[$key], 'new_value' => (string) $changed[$key], 'reason' => 'formula_metadata',
            ]);
        }
        if ($old === $new) {
            return;
        }
        $this->apply($table, $record->getKey(), $field, $old, $new, 'formula');
        // تا سیستم کد معرف حذف شود، ارجاع «کد معرف دیگری» که به کد قدیمی اشاره داشته هم تازه می‌شود.
        if ($record instanceof Technician && $old) {
            foreach (DB::table('technicians')->where('other_referral_code', $old)->get(['id']) as $referrer) {
                DB::table('registration_code_format_changes')->insert([
                    'table_name' => 'technicians', 'record_id' => $referrer->id, 'field_name' => 'other_referral_code',
                    'old_value' => $old, 'new_value' => $new, 'reason' => 'formula',
                ]);
                DB::table('technicians')->where('id', $referrer->id)->update(['other_referral_code' => $new]);
            }
        }
        $summary[$table]++;
    }
};
