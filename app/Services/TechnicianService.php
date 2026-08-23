<?php

namespace App\Services;

use App\DTOs\UpdateTechnicianPersonalInfoDTO;
use App\DTOs\UpdateTechnicianVehicleInfoDTO;
use App\DTOs\UpdateTechnicianBankInfoDTO;
use App\DTOs\UpdateTechnicianPasswordDTO;
use App\Models\Technician;
use App\Repositories\TechnicianRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class TechnicianService
{
    public function __construct(
        protected TechnicianRepository $technicianRepository
    ) {
    }

    public function updatePersonalInfo(Technician $technician, UpdateTechnicianPersonalInfoDTO $dto, ?UploadedFile $profilePhoto = null): array
    {
        try {
            $data = $dto->toArray();

            // آپلود عکس پروفایل اگر ارسال شده باشد
            if ($profilePhoto) {
                // حذف عکس قبلی اگر وجود داشته باشد
                if ($technician->profile_photo_path) {
                    Storage::disk('public')->delete($technician->profile_photo_path);
                }

                // ذخیره عکس جدید
                $path = $profilePhoto->store('technicians/profiles', 'public');
                $data['profile_photo_path'] = $path;
            }

            // به‌روزرسانی اطلاعات
            $updatedTechnician = $this->technicianRepository->updatePersonalInfo($technician, $data);

            return [
                'success' => true,
                'message' => 'اطلاعات شخصی با موفقیت به‌روزرسانی شد.',
                'data' => [
                    'technician' => [
                        'id' => $updatedTechnician->id,
                        'name' => $updatedTechnician->name,
                        'phone' => $updatedTechnician->phone,
                        'melicode' => $updatedTechnician->melicode,
                        'birth_date' => $updatedTechnician->birth_date,
                        'father_name' => $updatedTechnician->father_name,
                        'issued_from' => $updatedTechnician->issued_from,
                        'serial_number' => $updatedTechnician->serial_number,
                        'marital_status' => $updatedTechnician->marital_status,
                        'education_status' => $updatedTechnician->education_status,
                        'education_field' => $updatedTechnician->education_field,
                        'telephone' => $updatedTechnician->telephone,
                        'email' => $updatedTechnician->email,
                        'certificate_number' => $updatedTechnician->certificate_number,
                        'licence_date' => $updatedTechnician->licence_date,
                        'certificate_issue_date' => $updatedTechnician->certificate_issue_date,
                        'city' => $updatedTechnician->city,
                        'region' => $updatedTechnician->region,
                        'home_address' => $updatedTechnician->home_address,
                        'home_postal_code' => $updatedTechnician->home_postal_code,
                        'technician_type' => $updatedTechnician->technician_type,
                        'other_referral_code' => $updatedTechnician->other_referral_code,
                        'profile_photo_url' => $updatedTechnician->profile_photo_path
                            ? asset('storage/' . $updatedTechnician->profile_photo_path)
                            : null,
                    ]
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('خطا در به‌روزرسانی اطلاعات شخصی تکنسین: ' . $e->getMessage(), [
                'technician_id' => $technician->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی اطلاعات. لطفاً دوباره تلاش کنید.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    public function updateVehicleInfo(Technician $technician, UpdateTechnicianVehicleInfoDTO $dto): array
    {
        try {
            $data = $dto->toArray();

            // به‌روزرسانی اطلاعات وسیله نقلیه
            $updatedTechnician = $this->technicianRepository->updateVehicleInfo($technician, $data);

            return [
                'success' => true,
                'message' => 'اطلاعات وسیله نقلیه با موفقیت به‌روزرسانی شد.',
                'data' => [
                    'technician' => [
                        'id' => $updatedTechnician->id,
                        'vehicle_type' => $updatedTechnician->vehicle_type,
                        'car_model' => $updatedTechnician->car_model,
                        'car_color' => $updatedTechnician->car_color,
                        'car_plate' => $updatedTechnician->car_plate,
                        'car_year' => $updatedTechnician->car_year,
                        'car_fuel_type' => $updatedTechnician->car_fuel_type,
                        'car_vin' => $updatedTechnician->car_vin,
                        'car_insurance_code' => $updatedTechnician->car_insurance_code,
                        'car_insurance_expiry_date' => $updatedTechnician->car_insurance_expiry_date,
                    ]
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('خطا در به‌روزرسانی اطلاعات وسیله نقلیه تکنسین: ' . $e->getMessage(), [
                'technician_id' => $technician->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی اطلاعات. لطفاً دوباره تلاش کنید.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    public function updateBankInfo(Technician $technician, UpdateTechnicianBankInfoDTO $dto): array
    {
        try {
            $data = $dto->toArray();

            // به‌روزرسانی اطلاعات بانکی
            $updatedTechnician = $this->technicianRepository->updateBankInfo($technician, $data);

            return [
                'success' => true,
                'message' => 'اطلاعات بانکی با موفقیت به‌روزرسانی شد.',
                'data' => [
                    'technician' => [
                        'id' => $updatedTechnician->id,
                        'bank_shaba_number' => $updatedTechnician->bank_shaba_number,
                        'bank_name' => $updatedTechnician->bank_name,
                        'bank_card_number' => $updatedTechnician->bank_card_number,
                    ]
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('خطا در به‌روزرسانی اطلاعات بانکی تکنسین: ' . $e->getMessage(), [
                'technician_id' => $technician->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی اطلاعات. لطفاً دوباره تلاش کنید.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    public function updatePassword(Technician $technician, UpdateTechnicianPasswordDTO $dto): array
    {
        try {
            // بررسی صحت رمز عبور فعلی
            if (!Hash::check($dto->current_password, $technician->password)) {
                return [
                    'success' => false,
                    'message' => 'رمز عبور فعلی نادرست است.',
                    'error_code' => 'INCORRECT_PASSWORD'
                ];
            }

            // بررسی اینکه رمز عبور جدید با رمز فعلی متفاوت باشد
            if (Hash::check($dto->new_password, $technician->password)) {
                return [
                    'success' => false,
                    'message' => 'رمز عبور جدید نباید با رمز عبور فعلی یکسان باشد.',
                    'error_code' => 'SAME_PASSWORD'
                ];
            }

            // به‌روزرسانی رمز عبور
            $hashedPassword = Hash::make($dto->new_password);
            $this->technicianRepository->updatePassword($technician, $hashedPassword);

            // حذف تمام توکن‌های قبلی (logout از همه دستگاه‌ها به جز دستگاه فعلی)
            $technician->tokens()->where('id', '!=', $technician->currentAccessToken()->id)->delete();

            return [
                'success' => true,
                'message' => 'رمز عبور با موفقیت تغییر یافت. از سایر دستگاه‌ها خارج شدید.',
            ];
        } catch (\Exception $e) {
            \Log::error('خطا در تغییر رمز عبور تکنسین: ' . $e->getMessage(), [
                'technician_id' => $technician->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تغییر رمز عبور. لطفاً دوباره تلاش کنید.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }
    public function updateAtWork(Technician $technician): array
    {
        try {
            $at_wrok = 0;
            if ($technician->at_work == 0) {
                $at_wrok = 1;
            }
            $this->technicianRepository->updateAtWork($technician, $at_wrok);
            return [
                'success' => true,
                'message' => 'وضعیت حضور موفقیت ویرایش شد',
            ];
        } catch (\Exception $e) {
            
            return [
                'success' => false,
                'message' => 'خطا در تغییر وضعیت حضور',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    public function getMyOrders(Technician $technician, ?string $status = null, int $perPage = 15): array
    {
        try {
            $query = $this->technicianRepository->getTechnicianOrders($technician->id, $status);
            $orders = $query->paginate($perPage);

            $ordersData = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'must_notify' => $order->must_notify,
                    'date' => $order->date,
                    'time' => $order->time,
                    'is_urgent' => $order->is_urgent,
                    'description' => $order->des,
                    'technician_cancel_reason' => $order->technician_cancel_reason,
                    'technician_description' => $order->technician_des,
                    'pakar_price' => $order->pakar_price,
                    'technician_price' => $order->technician_price,
                    'extra_price' => $order->extra_price,
                    'discount_price' => $order->discount_price,
                    'payment_price' => $order->payment_price(true),
                    'set_off_at' => $order->set_off_at?->format('Y-m-d H:i:s'),
                    'arrived_at' => $order->arrived_at?->format('Y-m-d H:i:s'),
                    'started_at' => $order->started_at?->format('Y-m-d H:i:s'),
                    'finished_at' => $order->finished_at?->format('Y-m-d H:i:s'),
                    'is_fixed' => $order->is_fixed,
                    'is_technician_verified' => $order->is_technician_verified,
                    'female_count' => $order->female_count,
                    'male_count' => $order->male_count,
                    'unspecified_count' => $order->unspecified_count,
                    'image_path' => $order->image_path ? asset('storage/' . $order->image_path) : null,
                    'send_to_loop' => $order->send_to_loop?->format('Y-m-d H:i:s'),
                    'duration' => $order->duration,
                    'loop_description' => $order->loop_description,
                    'loop_cost_estimate' => $order->loop_cost_estimate,
                    'user_cancellation_reason' => $order->user_cancellation_reason,
                    'user_cancellation_date' => $order->user_cancellation_date?->format('Y-m-d H:i:s'),
                    'user_accept_date' => $order->user_accept_date?->format('Y-m-d H:i:s'),
                    'user_initial_accept' => $order->user_initial_accept?->format('Y-m-d H:i:s'),
                    'user_return_followup_description' => $order->user_return_followup_description,
                    'return_date' => $order->return_date,
                    'return_time' => $order->return_time,
                    'returned_at' => $order->returned_at?->format('Y-m-d H:i:s'),
                    'user_final_description' => $order->user_final_description,
                    'is_time_changed' => $order->is_time_changed,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                    'customer' => [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                        'last_name' => $order->user->last_name,
                        'full_name' => trim($order->user->name . ' ' . $order->user->last_name),
                        'phone' => $order->user->phone,
                        'email' => $order->user->email,
                    ],
                    'address' => $order->user_address ? [
                        'id' => $order->user_address->id,
                        'title' => $order->user_address->title,
                        'fname' => $order->user_address->fname,
                        'lname' => $order->user_address->lname,
                        'full_name' => $order->user_address->full_name,
                        'address' => $order->user_address->address,
                        'city' => $order->user_address->city,
                        'unit' => $order->user_address->unit,
                        'number' => $order->user_address->number,
                        'floor' => $order->user_address->floor,
                        'region' => $order->user_address->region,
                        'telephone' => $order->user_address->telephone,
                        'mobile' => $order->user_address->mobile,
                        'latitude' => $order->user_address->latitude,
                        'longitude' => $order->user_address->longitude,
                    ] : null,
                    'category' => [
                        'id' => $order->category->id,
                        'title' => $order->category->title,
                        'parent_id' => $order->category->parent_id,
                        'has_subcategory' => $order->category->has_subcategory,
                        'image_path' => $order->category->image_path ? asset('storage/' . $order->category->image_path) : null,
                    ],
                    'details' => $order->details->map(fn($detail) => [
                        'id' => $detail->id,
                        'step_name' => $detail->step_name,
                        'value' => $detail->value,
                    ]),
                    'extra_services' => $order->extra_services->map(fn($service) => [
                        'id' => $service->id,
                        'name' => $service->name,
                        'price' => $service->price,
                    ]),
                ];
            });

            return [
                'success' => true,
                'data' => [
                    'orders' => $ordersData,
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'from' => $orders->firstItem(),
                        'to' => $orders->lastItem(),
                    ]
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('خطا در دریافت لیست سفارشات تکنسین: ' . $e->getMessage(), [
                'technician_id' => $technician->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست سفارشات. لطفاً دوباره تلاش کنید.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }
}
