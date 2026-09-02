<?php

namespace App\Services;

use App\Models\Admin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminRoleService
{
    /**
     * ایجاد permissions پایه سیستم
     */
    public function createBasicPermissions(): array
    {
        try {
            $permissions = [
                'access-admin-panel' => 'دسترسی به پنل ادمین',
                'view-dashboard' => 'مشاهده داشبورد',
                'manage-admins' => 'مدیریت ادمین‌ها',
                'view-user' => 'مشاهده کاربران',
                'create-user' => 'ایجاد کاربران',
                'edit-user' => 'ویرایش کاربران',
                'delete-user' => 'حذف کاربران',
                'view-categories' => 'مشاهده دسته بندی‌ها',
                'create-categories' => 'ایجاد دسته بندی',
                'edit-categories' => 'ویرایش دسته بندی',
                'delete-categories' => 'حذف دسته بندی',

                'view-map' => 'مشاهده شعاع نقشه',
                'edit-map' => 'ویرایش شعاع نقشه',
                // مجوزهای جزئی برای مقالات
                'view-blogs' => 'مشاهده مقالات',
                'create-blogs' => 'ایجاد مقاله',
                'edit-blogs' => 'ویرایش مقالات',
                'delete-blogs' => 'حذف مقالات',

                // مجوزهای مدیریت تکنسین‌ها
                'view-technicians' => 'مشاهده تکنسین‌ها',
                'create-technicians' => 'ایجاد تکنسین',
                'edit-technicians' => 'ویرایش تکنسین‌ها',
                'delete-technicians' => 'حذف تکنسین‌ها',

                // مجوزهای مدیریت نظرسنجی تکنسین‌ها  
                'delete-technician-reviews' => 'حذف نظرسنجی تکنسین‌ها',

                // مجوزهای مدیریت فیلدها
                'view-fields' => 'مشاهده فیلدها',
                'create-fields' => 'ایجاد فیلدها',
                'edit-fields' => 'ویرایش فیلدها',
                'delete-fields' => 'حذف فیلدها',

                // مجوزهای مدیریت سوشال مدیا
                'view-socials' => 'مشاهده شبکه‌های اجتماعی',
                'edit-socials' => 'ویرایش شبکه‌های اجتماعی',
                'create-socials' => 'ایجاد شبکه‌های اجتماعی',
                'delete-socials' => 'حذف شبکه‌های اجتماعی',

                // مجوزهای مدیریت قوانین و حریم خصوصی
                'view-terms' => 'مشاهده قوانین و مقررات',
                'edit-terms' => 'ویرایش قوانین و مقررات',
                'delete-terms' => 'ویرایش قوانین و مقررات',
                'create-terms' => 'ایجاد قوانین و مقررات',

                'view-privacy' => 'مشاهده حریم خصوصی',
                'edit-privacy' => 'ویرایش حریم خصوصی',
                'delete-privacy' => 'ویرایش حریم خصوصی',
                'create-privacy' => 'ایجاد حریم خصوصی',

                'view-warranties' => 'ایجاد گارانتی',
                'edit-warranties' => 'ویرایش گارانتی',
                'delete-warranties' => 'حذف گارانتی',
                'create-warranties' => 'ایجاد گارانتی',

                'view-organization-terms' => 'مشاهده قوانین و مقررات سازمانی',
                'edit-organization-terms' => 'ویرایش قوانین و مقررات سازمانی',
                'delete-organization-terms' => 'حذف قوانین و مقررات سازمانی',
                'create-organization-terms' => 'ایجاد قوانین و مقررات سازمانی',

                // مجوزهای Resource های جدید
                'view-report-violations' => 'مدیریت گزارش تخلفات',
                'delete-report-violations' => 'حذف گزارش تخلفات',

                // مجوزهای مدیریت مکان‌ها 
                'view-provinces' => 'مشاهده استان‌ها',
                'create-provinces' => 'ایجاد استان',
                'edit-provinces' => 'ویرایش استان‌ها',
                'delete-provinces' => 'حذف استان‌ها',
                'view-cities' => 'مشاهده شهرها',
                'create-cities' => 'ایجاد شهر',
                'edit-cities' => 'ویرایش شهرها',
                'delete-cities' => 'حذف شهرها',
                'view-regions' => 'مشاهده مناطق',
                'create-regions' => 'ایجاد منطقه',
                'edit-regions' => 'ویرایش مناطق',
                'delete-regions' => 'حذف مناطق',

                // مجوزهای مدیریت باشگاه‌ها
                'view-clubs' => 'مشاهده باشگاه‌ها',
                'create-clubs' => 'ایجاد باشگاه',
                'edit-clubs' => 'ویرایش باشگاه‌ها',
                'delete-clubs' => 'حذف باشگاه‌ها',

                // مجوزهای مدیریت خدمات اضافی (قطعات و هزینه‌ها)
                'view-extra-services' => 'مشاهده قطعات و هزینه‌ها',
                'create-extra-services' => 'ایجاد قطعه و هزینه',
                'edit-extra-services' => 'ویرایش قطعات و هزینه‌ها',
                'delete-extra-services' => 'حذف قطعات و هزینه‌ها',

                // مجوزهای مدیریت ثبت نام آموزشی کاربران 
                'view-education-registerations' => 'مشاهده ثبت نام‌های آموزشی',
                'edit-education-registerations' => 'ویرایش ثبت نام‌های آموزشی',
                'delete-education-registrations' => 'حذف ثبت نام‌های آموزشی',

                // مجوزهای درخواست‌های بدهی
                'view-debt-requests' => 'مشاهده درخواست‌های بدهی',
                'edit-debt-requests' => 'ویرایش درخواست‌های بدهی',
                'delete-debt-requests' => 'حذف درخواست‌های بدهی',

                // مجوزهای درخواست‌های آموزشی 
                'view-education-requests' => 'مشاهده درخواست‌های آموزشی',
                'edit-education-requests' => 'ویرایش درخواست‌های آموزشی',
                'delete-education-requests' => 'حذف درخواست‌های آموزشی',

                // مجوزهای گزارش خرابی
                'create-fault-reports' => 'ایجاد گزارش‌های خرابی',
                'view-fault-reports' => 'مشاهده گزارش‌های خرابی',
                'edit-fault-reports' => 'ویرایش گزارش‌های خرابی',
                'delete-fault-reports' => 'حذف گزارش‌های خرابی',

                // مجوزهای برنامه‌های تشویقی
                'view-incentive-plans' => 'مشاهده برنامه‌های تشویقی',
                'create-incentive-plans' => 'ایجاد برنامه تشویقی',
                'edit-incentive-plans' => 'ویرایش برنامه‌های تشویقی',
                'delete-incentive-plans' => 'حذف برنامه‌های تشویقی',

                // مجوزهای درخواست‌های مرخصی
                'view-leave-requests' => 'مشاهده درخواست‌های مرخصی',
                'edit-leave-requests' => 'ویرایش درخواست‌های مرخصی',
                'delete-leave-requests' => 'حذف درخواست‌های مرخصی',

                // مجوزهای درخواست‌های نیروی انسانی
                'view-manpower-requests' => 'مشاهده درخواست‌های نیروی انسانی',
                'edit-manpower-requests' => 'ویرایش درخواست‌های نیروی انسانی',
                'delete-manpower-requests' => 'حذف درخواست‌های نیروی انسانی',


                // مجوزهای درخواست‌های نظرسنجی
                'view-poll-applications' => 'مشاهده درخواست‌های نظرسنجی',
                'edit-poll-applications' => 'ویرایش درخواست‌های نظرسنجی',
                'delete-poll-applications' => 'حذف درخواست‌های نظرسنجی',

                // مجوزهای درخواست‌های خاتمه
                'view-termination-requests' => 'مشاهده درخواست‌های خاتمه',
                'edit-termination-requests' => 'ویرایش درخواست‌های خاتمه',
                'delete-termination-requests' => 'حذف درخواست‌های خاتمه',

                // مجوزهای درخواست‌های انتقال 
                'view-transfer-requests' => 'مشاهده درخواست‌های انتقال',
                'edit-transfer-requests' => 'ویرایش درخواست‌های انتقال',
                'delete-transfer-requests' => 'حذف درخواست‌های انتقال',


                // مجوزهای مدیریت درباره ما
                'view-abouts' => 'مشاهده درباره ما',
                'create-about' => 'ایجاد درباره ما',
                'edit-about' => 'ویرایش درباره ما',
                'delete-about' => 'حذف درباره ما',

                // مجوزهای مدیریت تماس با ما
                'view-contacts' => 'مشاهده اطلاعات تماس',
                'create-contacts' => 'ایجاد اطلاعات تماس',
                'edit-contacts' => 'ویرایش اطلاعات تماس',
                'delete-contacts' => 'حذف اطلاعات تماس',

                // مجوزهای مدیریت پیام‌های تماس با ما
                'view-contact-us' => 'مشاهده پیام‌های تماس',
                'delete-contact-us' => 'حذف پیام‌های تماس',

                // مجوزهای مدیریت قراردادها    

                // مجوزهای مدیریت نرخ‌های نامه 
                'view-letter-rates' => 'مشاهده نرخ‌های نامه',
                'create-letter-rates' => 'ایجاد نرخ نامه',
                'edit-letter-rates' => 'ویرایش نرخ‌های نامه',
                'delete-letter-rates' => 'حذف نرخ‌های نامه',

                // مجوزهای مدیریت نرخ‌های نامه 
                'view-letter-rate-categories' => 'مشاهده دسته بندی نرخ‌های نامه',
                'create-letter-rate-categories' => 'ایجاد دسته بندی نرخ نامه',
                'edit-letter-rate-categories' => 'ویرایش دسته بندی نرخ‌های نامه',
                'delete-letter-rate-categories' => 'حذف دسته بندی نرخ‌های نامه',

                // مجوزهای مدیریت سفارشات 
                'view-orders' => 'مشاهده سفارشات',
                'create-orders' => 'ثبت سفارش',
                'edit-orders' => 'ویرایش سفارشات',

                // مجوزهای مدیریت سمت‌ها و مجوزها 

                // مجوزهای مدیریت برنامه خدمات
                'view-service-schedules' => 'مشاهده برنامه خدمات',
                'edit-service-schedules' => 'ویرایش برنامه خدمات',

                // مجوزهای مدیریت چت‌های تکنسین 
                'view-technician-chats' => 'مشاهده چت‌های تکنسین',
                // مجوزهای مدیریت نظرسنجی‌های تکنسین 
                'view-technician-polls' => 'مشاهده نظرسنجی‌های تکنسین',
                'edit-technician-polls' => 'ویرایش نظرسنجی‌های تکنسین',
                'delete-technician-polls' => 'حذف نظرسنجی‌های تکنسین',

                // مجوزهای مدیریت چت‌های کاربران
                'view-user-chats' => 'مشاهده چت‌های کاربران',

                // مجوزهای مدیریت آرشیو تصاویر
                'view-archive-images' => 'مشاهده آرشیو تصاویر',
                'create-archive-images' => 'افزودن تصویر به آرشیو',
                'delete-archive-images' => 'حذف تصاویر آرشیو',

                // مجوزهای مدیریت فعالیت‌های ورود/خروج
                'view-login-activities' => 'مشاهده فعالیت‌های ورود/خروج',

                // مجوزهای مدیریت QR Code
                'view-qr-codes' => 'مشاهده QR Code ها',
                'create-qr-codes' => 'ایجاد QR Code',
                'edit-qr-codes' => 'ویرایش QR Code ها',
                'delete-qr-codes' => 'حذف QR Code ها',

                'view-contract-request' => 'مشاهده درخواست‌های قرارداد سازمانی',
                'edit-contract-request' => 'ویرایش درخواست قرارداد سازمانی',
                'delete-contract-request' => 'حذف درخواست قرارداد سازمانی',
                'view-warranty-categories' => 'مشاهده دسته بندی‌های گارانتی',
                'create-warranty-categories' => 'ایجاد دسته بندی گارانتی',
                'edit-warranty-categories' => 'ویرایش دسته بندی گارانتی',
                'delete-warranty-categories' => 'حذف دسته بندی گارانتی',

                'view-digital-business-cards' => 'مشاهده کارت ویزیت',
                'edit-digital-business-cards' => 'ویرایش کارت ویزیت',
                'create-digital-business-cards' => 'ایجاد کارت ویزیت',
                'delete-digital-business-cards' => 'حذف کارت ویزیت',
                'create-insurance' => 'ایجاد بیمه تأمین اجتماعی',
                'edit-insurance' => 'ویرایش بیمه تأمین اجتماعی',
                'delete-insurance' => 'حذف بیمه تأمین اجتماعی',
                'view-loop-learn-registeration' => 'نمایش ثبت نام در کلاس‌های آموزشی',
                'delete-loop-learn-registeration' => 'حذف ثبت نام در کلاس‌های آموزشی',
                'create-supplementary-insurance' => 'ایجاد بیمه تکمیلی',
                'edit-supplementary-insurance' => 'ویرایش بیمه تکمیلی',
                'delete-supplementary-insurance' => 'حذف بیمه تکمیلی',
                'create-tools' => 'ایجاد اموال و ابزار محوله',
                'edit-tools' => 'ویرایش اموال و ابزار محوله',
                'delete-tools' => 'حذف اموال و ابزار محوله',

                'view-category-fields' => 'مشاهده مراحل شرطی',
                'edit-category-fields' => 'ویرایش مراحل شرطی',
                'delete-category-fields' => 'حذف مراحل شرطی',

                'view-admin-log' => 'مشاهده گزارش ورود و خروج مدیران',
                'view-pdf-document' => 'مشاهده پی دی اف ها',
                'edit-pdf-document' => 'ویرایش پی دی اف ها',

                'view-min-price' => 'مشاهده حداقل مبلغ اتحادیه',
                'edit-min-price' => 'ویرایش حداقل مبلغ اتحادیه',
            ];

            $created = [];
            foreach ($permissions as $name => $description) {
                $permission = Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'admin',
                ]);
                $permission->fa_name = $description;
                $permission->save();
                $created[] = $permission->name;
            }

            Log::info('Basic permissions created', ['permissions' => $created]);

            return [
                'success' => true,
                'message' => 'دسترسی‌های پایه با موفقیت ایجاد شدند',
                'permissions' => $created
            ];

        } catch (Exception $e) {
            Log::error('Failed to create basic permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد دسترسی‌ها',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ایجاد سمت‌های پایه سیستم
     */
    public function createBasicRoles(): array
    {
        try {
            $roles = [
                'super-admin' => [
                    'name' => 'مدیر کل',
                    'permissions' => 'all' // همه permissions را بده
                ],
                 
            ];

            $created = [];
            foreach ($roles as $roleName => $roleData) {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'admin',
                ]);

                // اختصاص دسترسی‌ها
                if ($roleData['permissions'] === 'all') {
                    // برای super-admin همه permissions را بده
                    $allPermissions = Permission::where('guard_name', 'admin')->pluck('name')->toArray();
                    $role->syncPermissions($allPermissions);
                } else {
                    $role->syncPermissions($roleData['permissions']);
                }
                $created[] = $role->name;
            }

            Log::info('Basic roles created', ['roles' => $created]);

            return [
                'success' => true,
                'message' => 'سمت‌های پایه با موفقیت ایجاد شدند',
                'roles' => $created
            ];

        } catch (Exception $e) {
            Log::error('Failed to create basic roles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد سمت‌ها',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ایجاد سوپر ادمین
     */
    public function createSuperAdmin(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                // بررسی وجود سمت super-admin
                $superAdminRole = Role::where('name', 'super-admin')
                    ->where('guard_name', 'admin')
                    ->first();

                if (!$superAdminRole) {
                    throw new Exception('سمت super-admin وجود ندارد. ابتدا سمت‌ها را ایجاد کنید.');
                }

                // ایجاد یا بروزرسانی سوپر ادمین
                $superAdmin = Admin::updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => Hash::make($data['password']),
                        'is_active' => true,
                        'staff_type' => Admin::STAFF_MANAGER,
                    ]
                );

                // اختصاص سمت
                $superAdmin->syncRoles(['super-admin']);

                Log::info('Super admin created/updated', [
                    'admin_id' => $superAdmin->id,
                    'email' => $superAdmin->email,
                    'name' => $superAdmin->name
                ]);

                return [
                    'success' => true,
                    'message' => 'سوپر ادمین با موفقیت ایجاد شد',
                    'admin' => $superAdmin,
                    'credentials' => [
                        'email' => $data['email'],
                        'password' => $data['password']
                    ]
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to create super admin', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد سوپر ادمین',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * راه‌اندازی کامل سیستم سمت و مجوز
     */
    public function setupCompleteRoleSystem(): array
    {
        try {
            // 1. ایجاد permissions
            $permissionsResult = $this->createBasicPermissions();
            if (!$permissionsResult['success']) {
                return $permissionsResult;
            }

            // 2. ایجاد roles
            $rolesResult = $this->createBasicRoles();
            if (!$rolesResult['success']) {
                return $rolesResult;
            }

            // 3. ایجاد سوپر ادمین
            $superAdminResult = $this->createSuperAdmin([
                'name' => 'مدیر کل سیستم',
                'email' => 'superadmin@loop.com',
                'password' => 'SuperAdmin@123'
            ]);

            if (!$superAdminResult['success']) {
                return $superAdminResult;
            }

            return [
                'success' => true,
                'message' => 'سیستم سمت و مجوز با موفقیت راه‌اندازی شد',
                'data' => [
                    'permissions' => $permissionsResult['permissions'],
                    'roles' => $rolesResult['roles'],
                    'super_admin_credentials' => $superAdminResult['credentials']
                ]
            ];

        } catch (Exception $e) {
            Log::error('Failed to setup complete role system', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در راه‌اندازی سیستم سمت و مجوز',
                'error' => $e->getMessage()
            ];
        }
    }
}