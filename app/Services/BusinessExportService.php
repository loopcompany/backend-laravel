<?php

namespace App\Services;

use App\Models\Club;
use App\Models\DiscountCode;
use App\Models\DiscountUse;
use App\Models\Order;
use App\Models\Organization;
use App\Models\ReferralCode;
use App\Models\Technician;
use App\Models\TechnicianTransaction;
use App\Models\User;
use App\Models\UserTransaction;
use App\Services\Excel\XlsxWriter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BusinessExportService
{
    public function __construct(protected XlsxWriter $writer)
    {
    }

    public static function reports(): array
    {
        return [
            'orders' => 'سفارش‌ها و درآمد',
            'users' => 'کاربران و مشتریان',
            'user_transactions' => 'تراکنش‌های مالی کاربران',
            'technicians' => 'تکنسین‌ها و عملکرد',
            'technician_transactions' => 'کمیسیون و تسویه تکنسین‌ها',
            'discounts' => 'تخفیف‌ها و کدهای معرف',
            'organizations' => 'سازمان‌ها و قراردادها',
        ];
    }

    public function download(string $report, ?string $from = null, ?string $until = null)
    {
        if (!array_key_exists($report, self::reports())) {
            throw new \InvalidArgumentException('نوع گزارش معتبر نیست.');
        }

        [$fromDate, $untilDate] = $this->dateRange($from, $until);
        $label = self::reports()[$report];
        $rows = $this->rowsFor($report, $fromDate, $untilDate);
        $summary = $this->summaryFor($report, $fromDate, $untilDate);

        return $this->writer->download(
            Str::slug($report) . '-' . now()->format('Ymd-His') . '.xlsx',
            [
                'خلاصه گزارش' => [
                    'headers' => ['عنوان', 'مقدار'],
                    'rows' => [
                        ['نوع گزارش', $label],
                        ['از تاریخ', $fromDate?->toDateString() ?? 'همه'],
                        ['تا تاریخ', $untilDate?->toDateString() ?? 'همه'],
                        ['زمان تولید', now()->format('Y-m-d H:i:s')],
                        ...$summary,
                    ],
                ],
                $label => $rows,
            ]
        );
    }

    private function rowsFor(string $report, ?Carbon $from, ?Carbon $until): array
    {
        return match ($report) {
            'orders' => [
                'headers' => ['شناسه سفارش', 'کد کاربر', 'نام مشتری', 'موبایل مشتری', 'دسته‌بندی', 'تکنسین', 'وضعیت سفارش', 'وضعیت پرداخت', 'قیمت پایه', 'قیمت تکنسین', 'هزینه اضافه', 'تخفیف', 'درصد تخفیف معرف', 'قیمت نهایی', 'پلتفرم', 'فوری', 'تاریخ سرویس', 'تاریخ ثبت'],
                'rows' => $this->orderRows($from, $until),
            ],
            'users' => [
                'headers' => ['شناسه', 'کد کاربر', 'نام', 'نام خانوادگی', 'موبایل', 'ایمیل', 'کد ملی', 'نوع حساب', 'وضعیت دسترسی', 'تأیید موبایل', 'موجودی کیف پول', 'تعداد سفارش', 'تعداد تراکنش', 'نام سازمان', 'تاریخ ثبت'],
                'rows' => $this->userRows($from, $until),
            ],
            'user_transactions' => [
                'headers' => ['شناسه تراکنش', 'شناسه کاربر', 'موبایل', 'مبلغ', 'نوع تراکنش', 'وضعیت', 'شماره مرجع', 'شناسه سفارش', 'توضیحات', 'تاریخ'],
                'rows' => $this->userTransactionRows($from, $until),
            ],
            'technicians' => [
                'headers' => ['شناسه', 'نام', 'موبایل', 'کد تکنسین', 'نوع', 'وضعیت تأیید', 'دسترسی', 'آنلاین', 'شهر', 'موجودی کیف پول', 'کمیسیون', 'تعداد سفارش', 'تعداد نظر', 'تاریخ ثبت'],
                'rows' => $this->technicianRows($from, $until),
            ],
            'technician_transactions' => [
                'headers' => ['شناسه تراکنش', 'تکنسین', 'موبایل', 'شناسه سفارش', 'مبلغ', 'کمیسیون', 'نوع', 'وضعیت', 'شماره مرجع', 'توضیحات', 'تاریخ'],
                'rows' => $this->technicianTransactionRows($from, $until),
            ],
            'discounts' => [
                'headers' => ['نوع', 'کد', 'کاربر/صاحب کد', 'درصد تخفیف', 'وضعیت', 'کد طرح', 'شناسه سفارش', 'استفاده‌کننده', 'تاریخ انقضا', 'تاریخ ثبت/استفاده'],
                'rows' => $this->discountRows($from, $until),
            ],
            'organizations' => [
                'headers' => ['شناسه سازمان', 'نام سازمان', 'کد سازمان', 'تلفن', 'مدیر', 'تلفن نماینده', 'وضعیت پروفایل', 'وضعیت قرارداد', 'تعداد کاربران', 'تعداد سفارش‌ها', 'تاریخ ثبت'],
                'rows' => $this->organizationRows($from, $until),
            ],
        };
    }

    private function summaryFor(string $report, ?Carbon $from, ?Carbon $until): array
    {
        $query = match ($report) {
            'orders' => Order::query(),
            'users' => User::query(),
            'user_transactions' => UserTransaction::query(),
            'technicians' => Technician::query(),
            'technician_transactions' => TechnicianTransaction::query(),
            'discounts' => DiscountCode::query(),
            'organizations' => Organization::query(),
        };

        $this->applyDateRange($query, $from, $until);
        $result = [['تعداد رکورد', number_format($query->count())]];

        if ($report === 'orders') {
            $result[] = ['مجموع قیمت پایه', number_format((float) $query->sum('pakar_price'))];
            $result[] = ['مجموع تخفیف ثبت‌شده', number_format((float) $query->sum('discount_price'))];
            $result[] = ['سفارش‌های پرداخت‌شده', number_format((clone $query)->where('payment_status', 1)->count())];
            $result[] = ['سفارش‌های تکمیل‌شده', number_format((clone $query)->where('status', 2)->count())];
        }

        if ($report === 'user_transactions') {
            $result[] = ['مجموع مبلغ تراکنش‌ها', number_format((float) $query->sum('price'))];
            $result[] = ['تراکنش‌های موفق', number_format((clone $query)->where('status', 100)->count())];
        }

        if ($report === 'technician_transactions') {
            $result[] = ['مجموع مبلغ', number_format((float) $query->sum('price'))];
            $result[] = ['مجموع کمیسیون', number_format((float) $query->sum('commission'))];
        }

        return $result;
    }

    private function orderRows(?Carbon $from, ?Carbon $until): iterable
    {
        $query = Order::query()->with([
            'user:id,code,name,last_name,phone',
            'category:id,title',
            'technician:id,name,phone',
        ]);
        $this->applyDateRange($query, $from, $until);

        foreach ($query->lazyById(500) as $order) {
            yield [
                $order->id,
                $order->user?->code,
                trim(($order->user?->name ?? '') . ' ' . ($order->user?->last_name ?? '')),
                $order->user?->phone,
                $order->category?->title,
                $order->technician?->name,
                $this->orderStatus($order->status),
                (int) $order->payment_status === 1 ? 'پرداخت شده' : 'پرداخت نشده',
                $order->pakar_price,
                $order->technician_price,
                $order->extra_price,
                $order->discount_price,
                $order->referral_discount_percent,
                $order->payment_price(false),
                $order->platform,
                $order->is_urgent ? 'بله' : 'خیر',
                $order->date,
                $order->created_at,
            ];
        }
    }

    private function userRows(?Carbon $from, ?Carbon $until): iterable
    {
        $query = User::query()
            ->with('organization:id,user_id,organization_name')
            ->withCount(['orders', 'transactions']);
        $this->applyDateRange($query, $from, $until);

        foreach ($query->lazyById(500) as $user) {
            yield [
                $user->id,
                $user->code,
                $user->name,
                $user->last_name,
                $user->phone,
                $user->email,
                $user->melicode,
                $user->account_type,
                $user->has_access ? 'فعال' : 'غیرفعال',
                $user->phone_verified_at ? 'تأیید شده' : 'تأیید نشده',
                $user->wallet,
                $user->orders_count,
                $user->transactions_count,
                $user->organization?->organization_name,
                $user->created_at,
            ];
        }
    }

    private function userTransactionRows(?Carbon $from, ?Carbon $until): iterable
    {
        $query = UserTransaction::query()->with('user:id,phone');
        $this->applyDateRange($query, $from, $until);

        foreach ($query->lazyById(500) as $transaction) {
            yield [
                $transaction->id,
                $transaction->user_id,
                $transaction->user?->phone,
                $transaction->price,
                $transaction->type,
                $this->transactionStatus($transaction->status),
                $transaction->referenceId,
                $transaction->order_id,
                $transaction->description,
                $transaction->created_at,
            ];
        }
    }

    private function technicianRows(?Carbon $from, ?Carbon $until): iterable
    {
        $query = Technician::query()->withCount(['orders', 'reviews']);
        $this->applyDateRange($query, $from, $until);

        foreach ($query->lazyById(500) as $technician) {
            yield [
                $technician->id,
                $technician->name,
                $technician->phone,
                $technician->referral_code,
                $technician->technician_type,
                $technician->approval_status,
                $technician->has_access ? 'فعال' : 'غیرفعال',
                $technician->is_online ? 'آنلاین' : 'آفلاین',
                $technician->city,
                $technician->wallet,
                $technician->commission,
                $technician->orders_count,
                $technician->reviews_count,
                $technician->created_at,
            ];
        }
    }

    private function technicianTransactionRows(?Carbon $from, ?Carbon $until): iterable
    {
        $query = TechnicianTransaction::query()->with('technician:id,name,phone');
        $this->applyDateRange($query, $from, $until);

        foreach ($query->lazyById(500) as $transaction) {
            yield [
                $transaction->id,
                $transaction->technician?->name,
                $transaction->technician?->phone,
                $transaction->order_id,
                $transaction->price,
                $transaction->commission,
                $transaction->type,
                $this->transactionStatus($transaction->status),
                $transaction->referenceId,
                $transaction->description,
                $transaction->created_at,
            ];
        }
    }

    private function discountRows(?Carbon $from, ?Carbon $until): iterable
    {
        $codes = DiscountCode::query()->with(['user:id,name,last_name', 'club:id,title']);
        $this->applyDateRange($codes, $from, $until);

        foreach ($codes->lazyById(500) as $code) {
            yield [
                'کد تخفیف',
                $code->code,
                trim(($code->user?->name ?? '') . ' ' . ($code->user?->last_name ?? '')),
                $code->discount_percent,
                $code->expiry_date && Carbon::parse($code->expiry_date)->isPast() ? 'منقضی' : 'فعال',
                $code->club?->title,
                null,
                null,
                $code->expiry_date,
                $code->created_at,
            ];
        }

        $referrals = ReferralCode::query()->with(['user:id,name,last_name', 'usedByUser:id,name,last_name']);
        $this->applyDateRange($referrals, $from, $until);

        foreach ($referrals->lazyById(500) as $code) {
            yield [
                'کد معرف',
                $code->code,
                trim(($code->user?->name ?? '') . ' ' . ($code->user?->last_name ?? '')),
                $code->discount_percent,
                $code->status,
                null,
                null,
                trim(($code->usedByUser?->name ?? '') . ' ' . ($code->usedByUser?->last_name ?? '')),
                null,
                $code->used_at ?? $code->created_at,
            ];
        }
    }

    private function organizationRows(?Carbon $from, ?Carbon $until): iterable
    {
        $query = Organization::query()->with('user:id,name,phone')->withCount(['contracts']);
        $this->applyDateRange($query, $from, $until);

        foreach ($query->lazyById(500) as $organization) {
            $userId = $organization->user?->id;

            yield [
                $organization->id,
                $organization->organization_name,
                $organization->organization_code,
                $organization->organization_phone,
                $organization->manager_full_name,
                $organization->agent_phone,
                $organization->profile_status,
                $organization->contract_status,
                $userId ? 1 : 0,
                $userId ? Order::where('user_id', $userId)->count() : 0,
                $organization->created_at,
            ];
        }
    }

    private function applyDateRange(Builder $query, ?Carbon $from, ?Carbon $until): void
    {
        $query
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($until, fn (Builder $q) => $q->where('created_at', '<=', $until));
    }

    private function dateRange(?string $from, ?string $until): array
    {
        return [
            $from ? Carbon::parse($from)->startOfDay() : null,
            $until ? Carbon::parse($until)->endOfDay() : null,
        ];
    }

    private function orderStatus(mixed $status): string
    {
        return match ((string) $status) {
            '0' => 'در انتظار',
            '1' => 'در حال انجام',
            '2' => 'انجام شده',
            '3' => 'لغو توسط کاربر',
            '4' => 'لغو توسط تکنسین',
            '5' => 'لغو توسط ادمین',
            '6' => 'منقضی شده',
            default => 'نامشخص',
        };
    }

    private function transactionStatus(mixed $status): string
    {
        return match ((string) $status) {
            '100' => 'موفق',
            '0' => 'در انتظار',
            '-200' => 'ناموفق',
            default => (string) $status,
        };
    }
}
