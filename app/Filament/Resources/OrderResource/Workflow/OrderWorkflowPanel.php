<?php

namespace App\Filament\Resources\OrderResource\Workflow;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderQuote;
use App\Models\OrderWorkflow;
use App\Models\Technician;
use App\Services\OrderWorkflowException;
use App\Services\OrderWorkflowService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Components\Actions\Action;
use Filament\Notifications\Notification;
use Morilog\Jalali\Jalalian;

/**
 * صفحه‌ی جزئیات سفارش بر اساس مراحل سند «فرآیند ثبت، بررسی، قیمت‌گذاری و ارجاع سفارش»:
 * استپر بالای صفحه + یک تب برای هر مرحله با اطلاعات و اقدام‌های همان مرحله + تاریخچه.
 */
class OrderWorkflowPanel
{
    /** ترتیب استپر؛ تب‌ها به همین ترتیب بعد از تب «ثبت سفارش» می‌آیند. */
    public const STEPS = [
        OrderWorkflow::STAGE_REVIEW,
        OrderWorkflow::STAGE_QUOTING,
        OrderWorkflow::STAGE_ASSIGNMENT,
        OrderWorkflow::STAGE_VISIT,
        OrderWorkflow::STAGE_REQUOTE,
        OrderWorkflow::STAGE_ONSITE_SERVICE,
        OrderWorkflow::STAGE_TRANSFER,
        OrderWorkflow::STAGE_CLOSED,
    ];

    public static function schema(): array
    {
        return [
            Infolists\Components\Section::make()
                ->schema([
                    Infolists\Components\ViewEntry::make('workflow_stepper')
                        ->hiddenLabel()
                        ->view('filament.infolists.order-workflow-stepper')
                        ->columnSpanFull(),
                ]),

            Infolists\Components\Tabs::make('workflow_tabs')
                ->activeTab(fn (Order $record) => self::activeTab($record))
                ->tabs([
                    self::registrationTab(),
                    self::reviewTab(),
                    self::quotingTab(),
                    self::assignmentTab(),
                    self::visitTab(),
                    self::requoteTab(),
                    self::onsiteTab(),
                    self::transferTab(),
                    self::closureTab(),
                    self::cancellationTab(),
                    self::historyTab(),
                ])
                ->columnSpanFull(),
        ];
    }

    /** تب مرحله‌ی فعلی (۱ = ثبت سفارش). تب لغو بعد از همه‌ی تب‌های مرحله است تا شماره‌ها جابه‌جا نشوند. */
    public static function activeTab(Order $record): int
    {
        $stage = self::workflow()->stage($record);

        if ($stage === OrderWorkflow::STAGE_CANCELLED) {
            return count(self::STEPS) + 2;
        }

        $index = array_search($stage, self::STEPS, true);

        return $index === false ? 1 : $index + 2;
    }

    /**
     * وضعیت هر گام استپر: done | current | upcoming | skipped | cancelled
     *
     * @return array<int, array{stage:string, label:string, state:string, hint:?string}>
     */
    public static function steps(Order $order): array
    {
        $workflow = self::workflow();
        $stage = $workflow->stage($order);
        $logs = $order->stageLogs()->get();
        $isLegacy = !$logs->contains('action', 'created');

        $entered = $logs->whereNotNull('to_stage')->groupBy('to_stage')->map(fn ($items) => $items->min('created_at'));
        $entered[OrderWorkflow::STAGE_REVIEW] ??= $order->created_at;

        $cancelledAt = null;
        if ($stage === OrderWorkflow::STAGE_CANCELLED) {
            $cancelledAt = $logs->firstWhere('to_stage', OrderWorkflow::STAGE_CANCELLED)?->from_stage;
            if (!in_array($cancelledAt, self::STEPS, true)) {
                $cancelledAt = $order->send_to_loop ? OrderWorkflow::STAGE_TRANSFER
                    : ($order->technician_id ? OrderWorkflow::STAGE_VISIT : OrderWorkflow::STAGE_REVIEW);
            }
        }

        $reference = $cancelledAt ?? $stage;
        $currentRank = array_search($reference, self::STEPS, true);

        $steps = [[
            'stage' => 'registered',
            'label' => 'ثبت سفارش',
            'state' => 'done',
            'hint' => self::jdt($order->created_at),
        ]];

        foreach (self::STEPS as $rank => $step) {
            $visited = $entered->has($step);

            if ($step === $reference) {
                $state = match (true) {
                    $cancelledAt !== null => 'cancelled',
                    $step === OrderWorkflow::STAGE_CLOSED && (int) $order->status === 2 => 'done',
                    default => 'current',
                };
            } elseif ($rank < $currentRank) {
                $state = match (true) {
                    $visited => 'done',
                    // سفارش‌های قبل از فرآیند تاریخچه ندارند
                    $isLegacy && !in_array($step, [OrderWorkflow::STAGE_REQUOTE, OrderWorkflow::STAGE_ONSITE_SERVICE, OrderWorkflow::STAGE_TRANSFER], true) => 'done',
                    $isLegacy && $step === OrderWorkflow::STAGE_ONSITE_SERVICE && $order->done_in_place => 'done',
                    $isLegacy && $step === OrderWorkflow::STAGE_TRANSFER && $order->send_to_loop => 'done',
                    default => 'skipped',
                };
            } else {
                $state = 'upcoming';
            }

            $hint = match ($state) {
                'done' => $visited ? self::jdt($entered[$step]) : null,
                'current' => self::currentHint($order, $step),
                'cancelled' => 'لغو شد',
                'skipped' => 'نیاز نشد',
                default => null,
            };

            $steps[] = ['stage' => $step, 'label' => OrderWorkflow::stageLabel($step), 'state' => $state, 'hint' => $hint];
        }

        return $steps;
    }

    /** وضعیت جزئی مرحله‌ی فعلی برای استپر. */
    public static function currentHint(Order $order, string $stage): ?string
    {
        $workflow = self::workflow();
        $details = $workflow->details($order);
        $open = $workflow->openQuote($order);

        return match ($stage) {
            OrderWorkflow::STAGE_REVIEW => OrderWorkflow::REVIEW_STATUSES[$details->review_status] ?? null,
            OrderWorkflow::STAGE_QUOTING, OrderWorkflow::STAGE_REQUOTE => $open
                ? OrderQuote::STATUSES[$open->status]
                : (($last = $workflow->customerQuote($order)) && $last->status === OrderQuote::STATUS_REJECTED ? 'قیمت رد شد — در انتظار تعیین تکلیف' : 'در انتظار ثبت قیمت'),
            OrderWorkflow::STAGE_ASSIGNMENT => $details->assignment_status
                ? OrderWorkflow::ASSIGNMENT_STATUSES[$details->assignment_status]
                : ($details->field_result === 'failed_needs_recoordination' ? 'مأموریت ناموفق — هماهنگی مجدد' : 'در انتظار تخصیص'),
            OrderWorkflow::STAGE_VISIT => match (true) {
                $details->visit_outcome === 'user_declined' => 'کاربر موافق ادامه نیست — تعیین تکلیف',
                (bool) $order->arrived_at => 'تکنسین در محل',
                (bool) $order->set_off_at => 'تکنسین در راه',
                default => 'در انتظار مراجعه',
            },
            OrderWorkflow::STAGE_ONSITE_SERVICE => $order->started_at ? 'در حال انجام' : 'آماده‌ی شروع',
            OrderWorkflow::STAGE_TRANSFER => $details->transfer_started_at ? 'در حال انتقال به شرکت' : 'در انتظار ثبت انتقال',
            OrderWorkflow::STAGE_CLOSED => 'نتیجه ثبت شد — پرونده باز',
            default => null,
        };
    }

    /**
     * وضعیت جزئی هر ردیف لیست سفارشات؛ فقط از رابطه‌های eager-load شده (workflow, quotes) تا کوئری اضافه نزند.
     */
    public static function rowHint(Order $order): ?string
    {
        $workflow = $order->workflow;
        $quotes = $order->relationLoaded('quotes') ? $order->quotes : $order->quotes()->get();
        $open = $quotes->whereIn('status', [OrderQuote::STATUS_PENDING_REVIEW, OrderQuote::STATUS_SENT])->sortByDesc('version')->first();
        $lastSent = $quotes->whereIn('status', [OrderQuote::STATUS_SENT, OrderQuote::STATUS_ACCEPTED, OrderQuote::STATUS_REJECTED])
            ->whereIn('kind', [OrderQuote::KIND_INITIAL, OrderQuote::KIND_REVISED])->sortByDesc('version')->first();

        return match ($order->workflow_stage ?? OrderWorkflow::STAGE_REVIEW) {
            OrderWorkflow::STAGE_REVIEW => OrderWorkflow::REVIEW_STATUSES[$workflow?->review_status ?? 'pending'],
            OrderWorkflow::STAGE_QUOTING, OrderWorkflow::STAGE_REQUOTE => match (true) {
                $open?->status === OrderQuote::STATUS_PENDING_REVIEW => 'پیشنهاد قیمت در انتظار بررسی پنل',
                $open?->status === OrderQuote::STATUS_SENT => 'در انتظار تأیید قیمت توسط کاربر',
                $lastSent?->status === OrderQuote::STATUS_REJECTED => 'قیمت رد شد — تعیین تکلیف',
                default => 'در انتظار ثبت قیمت',
            },
            OrderWorkflow::STAGE_ASSIGNMENT => $workflow?->assignment_status
                ? OrderWorkflow::ASSIGNMENT_STATUSES[$workflow->assignment_status]
                : ($workflow?->field_result === 'failed_needs_recoordination' ? 'مأموریت ناموفق — هماهنگی مجدد' : 'در انتظار تخصیص تکنسین'),
            OrderWorkflow::STAGE_VISIT => match (true) {
                $workflow?->visit_outcome === 'user_declined' => 'کاربر موافق ادامه نیست',
                (bool) $order->arrived_at => 'تکنسین در محل',
                (bool) $order->set_off_at => 'تکنسین در راه',
                default => 'در انتظار مراجعه — ' . trim(self::jd($order->date) . ' ' . $order->time),
            },
            OrderWorkflow::STAGE_ONSITE_SERVICE => $order->started_at ? 'در حال انجام خدمت' : 'آماده‌ی شروع خدمت',
            OrderWorkflow::STAGE_TRANSFER => match (true) {
                (bool) $workflow?->transfer_started_at => 'در حال انتقال — ' . (OrderWorkflow::TRANSFER_METHODS[$workflow->transfer_method] ?? ''),
                default => 'در انتظار ثبت انتقال',
            },
            OrderWorkflow::STAGE_CLOSED => match (true) {
                (int) $order->status === 2 => 'پرونده مختومه',
                !$order->technician_price => 'در انتظار قیمت نهایی',
                (int) $order->payment_status !== 1 => 'در انتظار تسویه',
                default => 'آماده‌ی مختومه شدن',
            },
            OrderWorkflow::STAGE_CANCELLED => trim('لغو از سوی ' . (['user' => 'کاربر', 'technician' => 'تکنسین', 'admin' => 'پنل', 'system' => 'سیستم'][$order->cancelled_by] ?? '-')
                . ($workflow?->device_return_required && !$order->returned_at ? ' — در انتظار عودت دستگاه' : '')),
            default => null,
        };
    }

    // ───────────────────────────── تب‌ها ─────────────────────────────

    private static function registrationTab(): Infolists\Components\Tabs\Tab
    {
        return Infolists\Components\Tabs\Tab::make('ثبت سفارش')
            ->icon('heroicon-o-inbox-arrow-down')
            ->schema([
                Infolists\Components\Section::make('اطلاعات ثبت')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')->label('شماره سفارش / کد پیگیری')->weight('bold'),
                        Infolists\Components\TextEntry::make('platform')
                            ->label('کانال ثبت')
                            ->formatStateUsing(fn ($state) => OrderWorkflowService::channelLabel($state))
                            ->badge(),
                        Infolists\Components\TextEntry::make('created_at')->label('تاریخ و ساعت ثبت')->formatStateUsing(fn ($state) => self::jdt($state)),
                        Infolists\Components\TextEntry::make('category.title')->label('دسته‌بندی خدمت'),
                        Infolists\Components\TextEntry::make('requested_time')
                            ->label('تاریخ و زمان درخواستی کاربر')
                            ->state(fn (Order $record) => self::requestedSchedule($record)),
                        Infolists\Components\TextEntry::make('is_urgent')
                            ->label('فوری')
                            ->formatStateUsing(fn ($state) => $state ? 'بله' : 'خیر')
                            ->badge()
                            ->color(fn ($state) => $state ? 'danger' : 'gray'),
                        Infolists\Components\TextEntry::make('technician_gender_label')
                            ->label('تکنسین درخواستی')
                            ->state(fn (Order $record) => $record->female_count > 0 ? 'خانم' : ($record->male_count > 0 ? 'آقا' : 'فرقی ندارد')),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('اطلاعات کاربر')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.code')->label('کد کاربری'),
                        Infolists\Components\TextEntry::make('user_full_name')
                            ->label('نام و نام خانوادگی')
                            ->state(fn (Order $record) => trim(($record->user?->name ?? '') . ' ' . ($record->user?->last_name ?? '')) ?: '-'),
                        Infolists\Components\TextEntry::make('user.account_type')
                            ->label('نوع حساب')
                            ->formatStateUsing(fn (?string $state) => self::accountTypeLabel($state))
                            ->badge(),
                        Infolists\Components\TextEntry::make('user.is_special')
                            ->label('سطح کاربر')
                            ->formatStateUsing(fn ($state) => $state ? 'کاربر ویژه' : 'کاربر عادی')
                            ->badge()
                            ->color(fn ($state) => $state ? 'warning' : 'gray'),
                        Infolists\Components\TextEntry::make('user.melicode')->label('کد ملی')->placeholder('-'),
                        Infolists\Components\TextEntry::make('user.phone')->label('شماره همراه')->placeholder('-'),
                        Infolists\Components\TextEntry::make('user.organization.organization_name')
                            ->label('سازمان / شرکت')
                            ->visible(fn (Order $record) => $record->user?->account_type !== 'individual')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('user.organization.agent_name')
                            ->label('نماینده‌ی سازمان')
                            ->formatStateUsing(fn ($state, Order $record) => trim($state . ' ' . ($record->user?->organization?->agent_phone ? '(' . $record->user->organization->agent_phone . ')' : '')))
                            ->visible(fn (Order $record) => $record->user?->account_type !== 'individual')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('نشانی محل مراجعه')
                    ->schema([
                        Infolists\Components\TextEntry::make('user_address.address')->label('آدرس')->columnSpanFull()->placeholder('-'),
                        Infolists\Components\TextEntry::make('user_address.city')->label('شهر')->placeholder('-'),
                        Infolists\Components\TextEntry::make('user_address.region')->label('منطقه')->placeholder('-'),
                        Infolists\Components\TextEntry::make('user_address.number')->label('پلاک')->placeholder('-'),
                        Infolists\Components\TextEntry::make('user_address.floor')->label('طبقه')->placeholder('-'),
                        Infolists\Components\TextEntry::make('user_address.unit')->label('واحد')->placeholder('-'),
                        Infolists\Components\TextEntry::make('user_address.room')->label('اتاق')->placeholder('-'),
                        Infolists\Components\ViewEntry::make('location_map')
                            ->hiddenLabel()
                            ->view('filament.infolists.location-map')
                            ->visible(fn (Order $record) => $record->user_address?->latitude && $record->user_address?->longitude)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Infolists\Components\Section::make('شرح درخواست و مشخصات دستگاه')
                    ->schema([
                        Infolists\Components\TextEntry::make('des')->label('توضیحات کاربر')->placeholder('-')->columnSpanFull(),
                        Infolists\Components\ViewEntry::make('order_details')
                            ->hiddenLabel()
                            ->view('filament.infolists.order-details')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('procurement_items')
                            ->label('تامین کالا و تجهیزات')
                            ->state(fn (Order $record) => self::procurementSummary($record))
                            ->visible(fn (Order $record) => !empty($record->procurement_items))
                            ->columnSpanFull(),
                        Infolists\Components\ImageEntry::make('image_path')
                            ->label('تصویر ارسالی کاربر')
                            ->height(220)
                            ->visible(fn (Order $record) => $record->image_path != null),
                    ]),
            ]);
    }

    private static function reviewTab(): Infolists\Components\Tabs\Tab
    {
        return self::stageTab(OrderWorkflow::STAGE_REVIEW, 'heroicon-o-clipboard-document-check', [
            Infolists\Components\Section::make('بررسی اطلاعات سفارش')
                ->description('کامل بودن اطلاعات، نوع خدمت، مشخصات دستگاه، محل مراجعه، اطلاعات کاربر و امکان ارجاع به تکنسین میدانی (بند ۳-۲).')
                ->schema([
                    self::badge('workflow.review_status', 'وضعیت بررسی', OrderWorkflow::REVIEW_STATUSES, ['pending' => 'warning', 'incomplete' => 'danger', 'approved' => 'success']),
                    Infolists\Components\TextEntry::make('workflow.service_type')
                        ->label('دسته‌ی درخواست')
                        ->formatStateUsing(fn ($state) => OrderWorkflow::SERVICE_TYPES[$state] ?? $state)
                        ->placeholder('تعیین نشده'),
                    Infolists\Components\TextEntry::make('workflow.customer_type')
                        ->label('نوع کاربر (شرایط قیمت‌گذاری)')
                        ->formatStateUsing(fn ($state) => OrderWorkflow::CUSTOMER_TYPES[$state] ?? $state)
                        ->placeholder('تعیین نشده'),
                    Infolists\Components\TextEntry::make('workflow.requester_name')
                        ->label('شخص درخواست‌دهنده')
                        ->formatStateUsing(fn ($state, Order $record) => trim($state . ' ' . ($record->workflow?->requester_phone ? '(' . $record->workflow->requester_phone . ')' : '')))
                        ->placeholder('-'),
                    Infolists\Components\TextEntry::make('workflow.contract_reference')->label('شناسه قرارداد / مجوز درخواست')->placeholder('-'),
                    Infolists\Components\TextEntry::make('workflow.reviewed_at')
                        ->label('تأیید اطلاعات')
                        ->formatStateUsing(fn ($state, Order $record) => self::jdt($state) . ($record->workflow?->reviewer ? ' — ' . $record->workflow->reviewer->name : ''))
                        ->placeholder('-'),
                    Infolists\Components\TextEntry::make('workflow.review_note')->label('توضیحات بررسی')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('workflow.route_to_loop_reason')
                        ->label('دلیل هدایت مستقیم به ارجاع دستگاه')
                        ->visible(fn (Order $record) => filled($record->workflow?->route_to_loop_reason))
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ], [
            self::classifyAction(),
            self::markIncompleteAction(),
            self::approveReviewAction(),
            self::routeToLoopAction(),
        ]);
    }

    private static function quotingTab(): Infolists\Components\Tabs\Tab
    {
        return self::stageTab(OrderWorkflow::STAGE_QUOTING, 'heroicon-o-calculator', [
            Infolists\Components\Section::make('قیمت اولیه و زمان خدمت')
                ->description('قیمت اولیه بر اساس اطلاعات کاربر، نوع خدمت و تعرفه‌های مصوب؛ اگر هزینه‌ی دقیق به بررسی فنی وابسته است، برآورد مقدماتی است و قیمت قطعی نیست (بند ۴-۳).')
                ->schema([
                    Infolists\Components\TextEntry::make('date')->label('تاریخ مراجعه')->formatStateUsing(fn ($state) => self::jd($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('time')->label('بازه‌ی زمانی مراجعه')->placeholder('-'),
                    Infolists\Components\TextEntry::make('pakar_price')->label('مبلغ پایه‌ی ثبت‌شده هنگام سفارش')->formatStateUsing(fn ($state) => self::money($state)),
                    Infolists\Components\TextEntry::make('user_initial_accept')->label('پذیرش اولیه‌ی کاربر')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\ViewEntry::make('initial_quotes')
                        ->hiddenLabel()
                        ->view('filament.infolists.order-quotes')
                        ->viewData(['kinds' => [OrderQuote::KIND_INITIAL]])
                        ->columnSpanFull(),
                ])
                ->columns(4),
        ], [
            self::createQuoteAction(OrderQuote::KIND_INITIAL),
            self::sendQuoteAction(),
            self::decideQuoteAction(),
            self::routeToLoopAction('route_to_loop_from_quoting'),
        ]);
    }

    private static function assignmentTab(): Infolists\Components\Tabs\Tab
    {
        return self::stageTab(OrderWorkflow::STAGE_ASSIGNMENT, 'heroicon-o-user-plus', [
            Infolists\Components\Section::make('تخصیص سفارش به تکنسین میدانی')
                ->description('تخصص، محدوده‌ی جغرافیایی، زمان مراجعه، ظرفیت کاری، تجهیزات موردنیاز و شرایط ویژه‌ی سفارش سازمانی (بند ۵-۲).')
                ->schema([
                    Infolists\Components\TextEntry::make('technician.name')->label('تکنسین')->placeholder('تخصیص داده نشده'),
                    Infolists\Components\TextEntry::make('technician.phone')->label('شماره تکنسین')->placeholder('-'),
                    self::badge('workflow.assignment_status', 'وضعیت مأموریت', OrderWorkflow::ASSIGNMENT_STATUSES, ['pending' => 'warning', 'accepted' => 'success', 'rejected' => 'danger']),
                    Infolists\Components\TextEntry::make('workflow.assigned_at')->label('زمان تخصیص')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('workflow.assignment_responded_at')->label('زمان پاسخ تکنسین')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('schedule')
                        ->label('زمان مراجعه')
                        ->state(fn (Order $record) => trim(self::jd($record->date) . ' — ' . $record->time, ' —')),
                    Infolists\Components\TextEntry::make('workflow.assignment_note')->label('توضیحات مأموریت (تجهیزات، شرایط ویژه)')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('workflow.assignment_reject_reason')
                        ->label('علت عدم امکان انجام (آخرین تکنسین)')
                        ->visible(fn (Order $record) => filled($record->workflow?->assignment_reject_reason))
                        ->color('danger')
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ], [
            self::assignTechnicianAction(),
            self::technicianResponseAction(),
        ]);
    }

    private static function visitTab(): Infolists\Components\Tabs\Tab
    {
        return self::stageTab(OrderWorkflow::STAGE_VISIT, 'heroicon-o-map-pin', [
            Infolists\Components\Section::make('مراجعه‌ی تکنسین به محل کاربر')
                ->description('تطبیق هویت سفارش، مشخصات دستگاه و درخواست کاربر با اطلاعات سامانه و ارزیابی امکان انجام خدمت در محل (بند ۶).')
                ->schema([
                    Infolists\Components\TextEntry::make('technician.name')->label('تکنسین')->placeholder('-'),
                    Infolists\Components\TextEntry::make('set_off_at')->label('زمان حرکت')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('arrived_at')->label('زمان رسیدن')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    self::badge('is_technician_verified', 'تأیید هویت تکنسین توسط کاربر', ['0' => 'در انتظار', '1' => 'تأیید شده', '2' => 'عدم تطابق'], ['0' => 'warning', '1' => 'success', '2' => 'danger']),
                    self::badge('workflow.visit_outcome', 'نتیجه‌ی بررسی اولیه', OrderWorkflow::VISIT_OUTCOMES, [
                        'serviceable_onsite' => 'success', 'needs_requote' => 'warning', 'transfer_to_loop' => 'info', 'user_declined' => 'danger', 'mission_failed' => 'danger',
                    ]),
                    Infolists\Components\TextEntry::make('workflow.visit_outcome_at')->label('زمان ثبت نتیجه')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('workflow.visit_outcome_note')->label('شرح نتیجه و اقدامات')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('declined_hint')
                        ->hiddenLabel()
                        ->state('کاربر با ادامه‌ی خدمت موافقت نکرده است. با «لغو سفارش» (بالای صفحه) تکلیف سفارش را مشخص کنید.')
                        ->color('danger')
                        ->visible(fn (Order $record) => self::isStage($record, OrderWorkflow::STAGE_VISIT) && $record->workflow?->visit_outcome === 'user_declined')
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('technician_opinion')->label('نظر تکنسین')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('emergency_help')
                        ->label('درخواست کمک اضطراری تکنسین')
                        ->formatStateUsing(fn ($state, Order $record) => $state . ' — ' . self::jdt($record->emergency_help_at))
                        ->color('danger')
                        ->weight('bold')
                        ->visible(fn (Order $record) => $record->emergency_help_at !== null)
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ], [
            self::simpleAction('record_set_off', 'ثبت حرکت تکنسین', 'heroicon-o-truck', 'gray',
                fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_VISIT) && !$r->set_off_at,
                fn (Order $r) => self::workflow()->recordSetOff($r), 'زمان حرکت ثبت شد'),
            self::simpleAction('record_arrival', 'ثبت رسیدن تکنسین', 'heroicon-o-map-pin', 'gray',
                fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_VISIT) && $r->set_off_at && !$r->arrived_at,
                fn (Order $r) => self::workflow()->recordArrival($r), 'زمان رسیدن ثبت شد'),
            self::visitOutcomeAction(),
        ]);
    }

    private static function requoteTab(): Infolists\Components\Tabs\Tab
    {
        return self::stageTab(OrderWorkflow::STAGE_REQUOTE, 'heroicon-o-arrow-path', [
            Infolists\Components\Section::make('قیمت‌گذاری مجدد خدمات و قطعات')
                ->description('قیمت جدید تفکیک‌شده (اجرت، قطعات، هزینه‌های اضافی مجاز، زمان تکمیل) باید توسط پنل تأیید و برای کاربر ارسال شود؛ بدون تأیید کاربر، خدمات اضافه یا مصرف قطعات مجاز نیست (بند ۷).')
                ->schema([
                    Infolists\Components\ViewEntry::make('revised_quotes')
                        ->hiddenLabel()
                        ->view('filament.infolists.order-quotes')
                        ->viewData(['kinds' => [OrderQuote::KIND_REVISED]])
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('extra_price')->label('مبلغ قطعات و خدمات اضافی ثبت‌شده در اپ تکنسین')->formatStateUsing(fn ($state) => self::money($state)),
                ]),
        ], [
            self::createQuoteAction(OrderQuote::KIND_REVISED),
            self::sendQuoteAction('send_requote'),
            self::discardQuoteAction(),
            self::decideQuoteAction('decide_requote'),
            self::resolveRequoteAction(),
        ]);
    }

    private static function onsiteTab(): Infolists\Components\Tabs\Tab
    {
        return self::stageTab(OrderWorkflow::STAGE_ONSITE_SERVICE, 'heroicon-o-wrench-screwdriver', [
            Infolists\Components\Section::make('انجام خدمت در محل کاربر')
                ->description('شروع خدمت فقط پس از تأیید قیمت و شرایط نهایی توسط کاربر (بند ۸-۱). اقدامات، قطعات مصرفی، نتیجه و زمان پایان در پرونده ثبت می‌شود (بند ۸-۲).')
                ->schema([
                    Infolists\Components\TextEntry::make('approved_price')
                        ->label('قیمت تأییدشده‌ی معتبر')
                        ->state(fn (Order $record) => ($q = self::workflow()->acceptedQuote($record))
                            ? number_format($q->total) . ' تومان (' . OrderQuote::KINDS[$q->kind] . ' نسخه ' . $q->version . ')'
                            : 'قیمتی تأیید نشده'),
                    Infolists\Components\TextEntry::make('started_at')->label('شروع خدمت')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('finished_at')->label('پایان خدمت')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('workflow.service_actions')->label('اقدامات انجام‌شده')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('workflow.service_parts_used')->label('قطعات مصرفی')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('workflow.service_result')->label('نتیجه‌ی کار')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('technician_in_place_description')->label('توضیحات تکنسین (اپ)')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('user_in_place_description')->label('توضیحات کاربر پس از انجام')->placeholder('-')->columnSpanFull(),
                ])
                ->columns(3),
        ], [
            self::simpleAction('start_service', 'شروع خدمت', 'heroicon-o-play', 'primary',
                fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_ONSITE_SERVICE) && !$r->started_at,
                fn (Order $r) => self::workflow()->startService($r), 'شروع خدمت ثبت شد'),
            self::completeServiceAction(),
            self::reasonAction('request_requote', 'نیاز به قیمت‌گذاری مجدد', 'heroicon-o-arrow-path', 'warning',
                [OrderWorkflow::STAGE_ONSITE_SERVICE, OrderWorkflow::STAGE_VISIT], 'شرح خدمات / قطعات اضافی موردنیاز',
                fn (Order $r, string $note) => self::workflow()->requestRequote($r, $note), 'سفارش به قیمت‌گذاری مجدد رفت',
                'ادامه‌ی بخش هزینه‌زای جدید منوط به قیمت‌گذاری مجدد و تأیید کاربر است (بند ۸-۳).'),
            self::reasonAction('send_to_transfer', 'ارجاع دستگاه به شرکت', 'heroicon-o-building-office', 'info',
                [OrderWorkflow::STAGE_ONSITE_SERVICE], 'دلیل ارجاع (نیاز به تجهیزات، نبود قطعه، بررسی تکمیلی…)',
                fn (Order $r, string $note) => self::workflow()->sendToTransfer($r, $note), 'سفارش به مرحله‌ی ارجاع رفت'),
        ]);
    }

    private static function transferTab(): Infolists\Components\Tabs\Tab
    {
        return self::stageTab(OrderWorkflow::STAGE_TRANSFER, 'heroicon-o-building-office', [
            Infolists\Components\Section::make('ارجاع دستگاه به شرکت لوپ')
                ->description('روش انتقال، مشخصات و وضعیت ظاهری دستگاه، لوازم همراه و علت ارجاع پیش از انتقال؛ تحویل فیزیکی با رسید ثبت می‌شود (بند ۹).')
                ->schema([
                    Infolists\Components\TextEntry::make('workflow.transfer_method')
                        ->label('روش انتقال')
                        ->formatStateUsing(fn ($state) => OrderWorkflow::TRANSFER_METHODS[$state] ?? $state)
                        ->placeholder('ثبت نشده'),
                    Infolists\Components\TextEntry::make('workflow.transfer_started_at')->label('زمان شروع انتقال')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('send_to_loop')->label('زمان اعزام محصول به لوپ')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('workflow.transfer_reason')->label('علت ارجاع')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('workflow.transfer_device_condition')->label('مشخصات و وضعیت ظاهری دستگاه')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('workflow.transfer_accessories')->label('لوازم همراه')->placeholder('-')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('workflow.transfer_courier_info')
                        ->label('مشخصات مأموریت پیک / اطلاعات بسته / نتیجه‌ی تحویل')
                        ->visible(fn (Order $record) => $record->workflow?->transfer_method === 'courier')
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('workflow.transfer_received_by')->label('مسئول دریافت در شرکت')->placeholder('-'),
                    Infolists\Components\TextEntry::make('workflow.transfer_received_at')->label('زمان تحویل در شرکت')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('workflow.transfer_receipt_number')->label('شماره رسید')->placeholder('-')->copyable(),
                    Infolists\Components\TextEntry::make('workflow.transfer_custody_note')->label('زنجیره‌ی تحویل (کاربر تا پذیرش شرکت)')->placeholder('-')->columnSpanFull(),
                ])
                ->columns(3),
        ], [
            self::startTransferAction(),
            self::confirmReceiptAction(),
        ]);
    }

    private static function closureTab(): Infolists\Components\Tabs\Tab
    {
        return self::stageTab(OrderWorkflow::STAGE_CLOSED, 'heroicon-o-flag', [
            Infolists\Components\Section::make('نتیجه‌ی مراحل سفارش')
                ->schema([
                    self::badge('workflow.field_result', 'نتیجه', OrderWorkflow::FIELD_RESULTS, [
                        'done_onsite' => 'success', 'transferred_to_loop' => 'info', 'cancelled_by_user' => 'danger',
                        'cancelled_not_feasible' => 'danger', 'failed_needs_recoordination' => 'warning',
                    ]),
                    Infolists\Components\TextEntry::make('workflow.field_closed_at')->label('پایان سفارش میدانی')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('پرونده‌ی اصلی')
                        ->formatStateUsing(fn ($state) => (int) $state === 2 ? 'مختومه' : 'باز')
                        ->badge()
                        ->color(fn ($state) => (int) $state === 2 ? 'success' : 'warning'),
                    Infolists\Components\TextEntry::make('close_checklist')
                        ->label('شرایط مختومه شدن پرونده (بند ۱۱-۵)')
                        ->state(fn (Order $record) => (int) $record->status === 2 ? 'پرونده مختومه شده است' : (implode('، ', self::workflow()->closeBlockers($record)) ?: 'همه‌ی شرایط برقرار است'))
                        ->color(fn (Order $record) => (int) $record->status === 2 || !self::workflow()->closeBlockers($record) ? 'success' : 'danger')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Infolists\Components\Section::make('مبالغ و تسویه')
                ->description('قیمت اولیه، اصلاح‌شده و نهایی جدا از هم ثبت می‌شوند (بند ۱۲-۲).')
                ->schema([
                    Infolists\Components\TextEntry::make('technician_price')->label('قیمت نهایی (بدون تخفیف و خدمات اضافی)')->formatStateUsing(fn ($state) => self::money($state))->placeholder('ثبت نشده'),
                    Infolists\Components\TextEntry::make('extra_price')->label('قطعات و خدمات اضافی')->formatStateUsing(fn ($state) => self::money($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('discount_price')->label('تخفیف')->formatStateUsing(fn ($state) => self::money($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('discount_code')
                        ->label('کد تخفیف')
                        ->state(fn (Order $record) => $record->discountUse?->discount_code ? $record->discountUse->discount_code->code . ' (' . $record->discountUse->discount_code->discount_percent . '%)' : null)
                        ->visible(fn (Order $record) => (bool) $record->discountUse?->discount_code)
                        ->badge(),
                    Infolists\Components\TextEntry::make('payable')->label('مبلغ قابل پرداخت')->state(fn (Order $record) => self::money($record->payment_price()))->weight('bold'),
                    self::badge('payment_status', 'وضعیت پرداخت', ['0' => 'پرداخت نشده', '1' => 'پرداخت شده'], ['0' => 'danger', '1' => 'success']),
                    Infolists\Components\TextEntry::make('payment_method')
                        ->label('روش / کانال پرداخت')
                        ->formatStateUsing(fn ($state, Order $record) => (Order::PAYMENT_METHODS[$state] ?? $state) . ($record->payment_channel ? ' (' . (Order::PAYMENT_CHANNELS[$record->payment_channel] ?? $record->payment_channel) . ')' : '')),
                    Infolists\Components\TextEntry::make('remaining_amount')->label('مانده')->formatStateUsing(fn ($state) => self::money($state))->placeholder('-'),
                    Infolists\Components\ViewEntry::make('all_quotes')
                        ->hiddenLabel()
                        ->view('filament.infolists.order-quotes')
                        ->viewData(['kinds' => [OrderQuote::KIND_INITIAL, OrderQuote::KIND_REVISED, OrderQuote::KIND_FINAL]])
                        ->columnSpanFull(),
                ])
                ->columns(4),

            Infolists\Components\Section::make('فرآیند تعمیرات داخل شرکت و تحویل نهایی')
                ->description('کارشناسی، تعیین هزینه‌ی قطعی، پیش‌پرداخت و تحویل نهایی تابع فرآیند اختصاصی خود است (بند ۱۲-۸)؛ از «ویرایش» سفارش ثبت می‌شود.')
                ->visible(fn (Order $record) => $record->returnsGoodsToUser())
                ->schema([
                    Infolists\Components\TextEntry::make('loop_cost_estimate')->label('هزینه‌ی تقریبی لوپ')->formatStateUsing(fn ($state) => self::money($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('prepayment')->label('درصد پیش‌پرداخت')->formatStateUsing(fn ($state) => $state ? $state . '%' : '-'),
                    self::badge('prepayment_payment_status', 'پیش‌پرداخت', ['0' => 'پرداخت نشده', '1' => 'پرداخت شده'], ['0' => 'danger', '1' => 'success']),
                    Infolists\Components\TextEntry::make('duration')->label('مدت انجام (روز کاری)')->placeholder('-'),
                    Infolists\Components\TextEntry::make('delivery_status')
                        ->label('وضعیت تحویل به کاربر')
                        ->formatStateUsing(fn ($state) => Order::DELIVERY_STATUSES[$state] ?? $state)
                        ->placeholder(Order::DELIVERY_STATUSES['pending']),
                    Infolists\Components\TextEntry::make('delivered_at')->label('زمان تحویل')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                    Infolists\Components\TextEntry::make('delivery_receiver_type')
                        ->label('تحویل‌گیرنده')
                        ->formatStateUsing(fn ($state, Order $record) => $state === 'representative'
                            ? trim(($record->delivery_receiver_name ?? '') . ' ' . ($record->delivery_receiver_mobile ? '(' . $record->delivery_receiver_mobile . ')' : '')) ?: Order::DELIVERY_RECEIVER_TYPES[$state]
                            : (Order::DELIVERY_RECEIVER_TYPES[$state] ?? '-'))
                        ->placeholder('ثبت نشده'),
                    Infolists\Components\TextEntry::make('delivery_method')->label('روش ارسال')->formatStateUsing(fn ($state) => Order::DELIVERY_METHODS[$state] ?? $state)->placeholder('ثبت نشده'),
                    Infolists\Components\TextEntry::make('delivery_confirmed_at')
                        ->label('تأیید و ارسال برای کاربر')
                        ->state(fn (Order $record) => OrderResource::deliveryConfirmationLabel($record))
                        ->badge()
                        ->color(fn (Order $record) => $record->delivery_confirmed_at ? 'success' : 'warning'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('توضیحات پایانی')
                ->schema([
                    Infolists\Components\TextEntry::make('admin_in_place_description')->label('توضیحات مدیریت')->placeholder('-'),
                    Infolists\Components\TextEntry::make('user_final_description')->label('توضیحات نهایی کاربر')->placeholder('-'),
                ])
                ->columns(2)
                ->collapsible(),
        ], [
            self::finalPriceAction(),
            self::closeFileAction(),
        ]);
    }

    private static function cancellationTab(): Infolists\Components\Tabs\Tab
    {
        return Infolists\Components\Tabs\Tab::make('لغو سفارش')
            ->icon('heroicon-o-x-circle')
            ->visible(fn (Order $record) => self::isStage($record, OrderWorkflow::STAGE_CANCELLED))
            ->schema([
                Infolists\Components\Section::make('لغو سفارش')
                    ->description('در صورت در اختیار بودن دستگاه، لغو به‌تنهایی به معنی پایان مسئولیت نگهداری نیست و عودت باید ثبت شود (بند ۱۰-۵).')
                    ->schema([
                        Infolists\Components\TextEntry::make('cancelled_by')
                            ->label('منبع لغو')
                            ->formatStateUsing(fn ($state) => ['user' => 'کاربر', 'technician' => 'تکنسین', 'admin' => 'پنل', 'system' => 'سیستم'][$state] ?? $state)
                            ->badge()
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('cancelled_at')->label('زمان لغو')->formatStateUsing(fn ($state) => self::jdt($state))->placeholder('-'),
                        Infolists\Components\TextEntry::make('workflow.cancel_reason_type')
                            ->label('علت لغو')
                            ->formatStateUsing(fn ($state) => OrderWorkflow::CANCEL_REASONS[$state] ?? $state)
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('workflow.cancel_reason')->label('شرح علت')->placeholder('-')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('workflow.cancel_financial_note')->label('تعیین تکلیف مالی')->placeholder('-')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('device_return')
                            ->label('عودت دستگاه')
                            ->state(fn (Order $record) => !$record->workflow?->device_return_required
                                ? 'دستگاه در اختیار تکنسین / شرکت نبوده'
                                : ($record->returned_at ? 'عودت شد در ' . self::jdt($record->returned_at) : 'در انتظار عودت دستگاه'))
                            ->badge()
                            ->color(fn (Order $record) => $record->workflow?->device_return_required && !$record->returned_at ? 'warning' : 'success'),
                    ])
                    ->columns(3),
                Infolists\Components\Actions::make([
                    self::reasonAction('confirm_device_returned', 'ثبت عودت دستگاه', 'heroicon-o-arrow-uturn-left', 'success',
                        [OrderWorkflow::STAGE_CANCELLED], 'توضیحات عودت (تحویل‌گیرنده، روش)',
                        fn (Order $r, string $note) => self::workflow()->confirmDeviceReturned($r, $note), 'عودت دستگاه ثبت شد',
                        visible: fn (Order $r) => (bool) $r->workflow?->device_return_required && !$r->returned_at),
                ]),
            ]);
    }

    private static function historyTab(): Infolists\Components\Tabs\Tab
    {
        return Infolists\Components\Tabs\Tab::make('تاریخچه')
            ->icon('heroicon-o-clock')
            ->badge(fn (Order $record) => $record->stageLogs()->count() ?: null)
            ->schema([
                Infolists\Components\ViewEntry::make('workflow_history')
                    ->hiddenLabel()
                    ->view('filament.infolists.order-workflow-history')
                    ->columnSpanFull(),
            ]);
    }

    private static function stageTab(string $stage, string $icon, array $schema, array $actions): Infolists\Components\Tabs\Tab
    {
        return Infolists\Components\Tabs\Tab::make(OrderWorkflow::stageLabel($stage))
            ->icon(OrderWorkflow::STAGE_ICONS[$stage] ?? $icon)
            ->badge(fn (Order $record) => self::isStage($record, $stage) ? 'مرحله‌ی فعلی' : null)
            ->badgeColor(OrderWorkflow::STAGE_COLORS[$stage] ?? 'warning')
            ->schema(array_merge($schema, [Infolists\Components\Actions::make($actions)->key('actions_' . $stage)]));
    }

    // ───────────────────────────── اقدام‌ها ─────────────────────────────

    /** «لغو سفارش» برای هدر صفحه‌ی جزئیات (همه‌ی مراحل به جز پایان و لغو). */
    public static function cancelAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('workflow_cancel')
            ->label('لغو سفارش')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Order $record) => OrderResource::canEdit($record)
                && !in_array(self::workflow()->stage($record), [OrderWorkflow::STAGE_CANCELLED], true)
                && (int) $record->status !== 2)
            ->modalHeading('لغو سفارش')
            ->modalDescription(fn (Order $record) => self::paidSummary($record))
            ->fillForm(fn (Order $record) => [
                'cancel_reason_type' => $record->workflow?->visit_outcome === 'user_declined' ? 'user_request' : null,
                'device_return_required' => $record->send_to_loop !== null || $record->workflow?->transfer_started_at !== null,
            ])
            ->form([
                Forms\Components\Select::make('cancel_reason_type')->label('علت لغو')->options(OrderWorkflow::CANCEL_REASONS)->required(),
                Forms\Components\Textarea::make('cancel_reason')->label('شرح علت')->required()->rows(3)->maxLength(2000),
                Forms\Components\Textarea::make('cancel_financial_note')
                    ->label('تعیین تکلیف مالی')
                    ->helperText('لغو پیش از شروع خدمت: بر اساس مبالغ دریافتی و شرایط توافق‌شده؛ پس از شروع: هزینه‌های قابل اعمال بر اساس شرایط تأییدشده‌ی کاربر (بند ۱۰-۳ و ۱۰-۴).')
                    ->rows(3)
                    ->maxLength(2000),
                Forms\Components\Toggle::make('device_return_required')
                    ->label('دستگاه در اختیار تکنسین / شرکت است و باید عودت شود')
                    ->disabled(fn (Order $record) => $record->send_to_loop !== null || $record->workflow?->transfer_started_at !== null)
                    ->dehydrated(),
            ])
            ->action(function (Order $record, array $data, \Filament\Actions\Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->cancel($record, $data), 'سفارش لغو شد');
            });
    }

    private static function classifyAction(): Action
    {
        return self::workflowAction('classify', 'دسته‌بندی و نوع کاربر', 'heroicon-o-tag', 'gray',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_REVIEW, OrderWorkflow::STAGE_QUOTING, OrderWorkflow::STAGE_ASSIGNMENT))
            ->modalHeading('تعیین دسته‌ی درخواست و نوع کاربر')
            ->fillForm(fn (Order $record) => $record->workflow?->only(['service_type', 'customer_type', 'requester_name', 'requester_phone', 'contract_reference']) ?? [])
            ->form([
                Forms\Components\Radio::make('service_type')->label('دسته‌ی درخواست (بند ۳-۴)')->options(OrderWorkflow::SERVICE_TYPES)->required(),
                Forms\Components\Select::make('customer_type')->label('نوع کاربر (بند ۳-۶)')->options(OrderWorkflow::CUSTOMER_TYPES)->required()->live(),
                Forms\Components\Grid::make(3)
                    ->visible(fn (Forms\Get $get) => in_array($get('customer_type'), ['organization', 'company'], true))
                    ->schema([
                        Forms\Components\TextInput::make('requester_name')->label('شخص درخواست‌دهنده')->maxLength(191)->required(),
                        Forms\Components\TextInput::make('requester_phone')->label('تلفن درخواست‌دهنده')->maxLength(32),
                        Forms\Components\TextInput::make('contract_reference')->label('شناسه قرارداد / مجوز')->maxLength(191),
                    ]),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->classify($record, $data), 'اطلاعات بررسی ذخیره شد');
            });
    }

    private static function markIncompleteAction(): Action
    {
        return self::reasonAction('mark_incomplete', 'اطلاعات ناقص است', 'heroicon-o-exclamation-triangle', 'warning',
            [OrderWorkflow::STAGE_REVIEW], 'اطلاعات ناقص / موردنیاز (برای کاربر نمایش داده می‌شود)',
            fn (Order $r, string $note) => self::workflow()->markIncomplete($r, $note), 'سفارش برای تکمیل اطلاعات علامت خورد',
            'تا تکمیل اطلاعات ضروری، تخصیص تکنسین انجام نمی‌شود (بند ۳-۳). به کاربر اطلاع داده می‌شود.');
    }

    private static function approveReviewAction(): Action
    {
        return self::workflowAction('approve_review', 'تأیید اطلاعات و رفتن به قیمت‌گذاری', 'heroicon-o-check-badge', 'success',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_REVIEW))
            ->form([Forms\Components\Textarea::make('note')->label('توضیحات (اختیاری)')->rows(2)->maxLength(1000)])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->approveReview($record, $data['note'] ?? null), 'اطلاعات سفارش تأیید شد');
            });
    }

    private static function routeToLoopAction(string $name = 'route_to_loop'): Action
    {
        return self::reasonAction($name, 'ارجاع مستقیم دستگاه به شرکت', 'heroicon-o-building-office', 'info',
            [OrderWorkflow::STAGE_REVIEW, OrderWorkflow::STAGE_QUOTING], 'دلیل (چرا انجام در محل امکان‌پذیر نیست)',
            fn (Order $r, string $note) => self::workflow()->routeToLoop($r, $note), 'سفارش به مسیر ارجاع دستگاه هدایت شد',
            'ماهیت درخواست نشان می‌دهد انجام خدمت در محل امکان‌پذیر نیست (بند ۳-۵).');
    }

    private static function createQuoteAction(string $kind): Action
    {
        $isInitial = $kind === OrderQuote::KIND_INITIAL;
        $stages = $isInitial ? [OrderWorkflow::STAGE_QUOTING] : [OrderWorkflow::STAGE_REQUOTE];

        return self::workflowAction('create_quote_' . $kind, $isInitial ? 'ثبت قیمت اولیه و زمان' : 'ثبت قیمت جدید', 'heroicon-o-currency-dollar', 'primary',
            fn (Order $r) => self::isStage($r, ...$stages) && !self::workflow()->openQuote($r))
            ->modalHeading($isInitial ? 'قیمت اولیه و زمان خدمت' : 'قیمت‌گذاری مجدد (تفکیک‌شده)')
            ->modalDescription(fn (Order $record) => self::pricingContext($record))
            ->modalWidth('3xl')
            ->fillForm(fn (Order $record) => [
                'is_estimate' => $isInitial,
                'requires_user_approval' => true,
                'send' => true,
                'visit_date' => $record->date,
                'visit_time' => $record->time,
                'service_fee' => ($q = self::workflow()->customerQuote($record)) ? $q->service_fee : ($isInitial ? (int) $record->pakar_price : null),
                'parts_fee' => $q?->parts_fee,
                'extra_fee' => $q?->extra_fee,
            ])
            ->form([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('service_fee')->label('هزینه‌ی خدمات و اجرت')->numeric()->minValue(0)->suffix('تومان')->required(),
                    Forms\Components\TextInput::make('parts_fee')->label('هزینه‌ی (احتمالی) قطعات')->numeric()->minValue(0)->suffix('تومان'),
                    Forms\Components\TextInput::make('extra_fee')->label('هزینه‌های اضافی مجاز')->numeric()->minValue(0)->suffix('تومان'),
                ]),
                Forms\Components\Textarea::make('change_reason')->label('علت تغییر هزینه')->rows(2)->required()->visible(!$isInitial)->maxLength(2000),
                Forms\Components\Textarea::make('parts_description')->label('قطعات موردنیاز')->rows(2)->maxLength(2000),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('estimated_duration')->label($isInitial ? 'زمان تقریبی انجام خدمت' : 'زمان تقریبی تکمیل خدمت')->placeholder('مثلاً ۲ ساعت / ۳ روز کاری')->maxLength(191)->required(!$isInitial),
                    Forms\Components\DatePicker::make('visit_date')->label('تاریخ مراجعه')->jalali()->visible($isInitial)->required($isInitial),
                    Forms\Components\Select::make('visit_time')->label('بازه‌ی زمانی مراجعه')
                        ->options(fn (Order $record) => Order::DISPATCH_TIME_SLOTS + (filled($record->time) && !isset(Order::DISPATCH_TIME_SLOTS[$record->time]) ? [$record->time => $record->time] : []))
                        ->visible($isInitial)->required($isInitial),
                ]),
                Forms\Components\Textarea::make('description')->label('توضیحات برای کاربر')->rows(2)->maxLength(2000),
                Forms\Components\Toggle::make('is_estimate')->label('برآورد مقدماتی (قیمت قطعی نیست)')->visible($isInitial),
                Forms\Components\Toggle::make('requires_user_approval')->label('نیاز به تأیید کاربر')->visible($isInitial)->live(),
                Forms\Components\Toggle::make('send')->label('ارسال فوری برای کاربر')->helperText('خاموش: به‌صورت پیش‌نویس ذخیره می‌شود.'),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) use ($kind) {
                self::attempt($action, $livewire, $record, function () use ($record, $data, $kind) {
                    self::workflow()->createQuote($record, $kind, $data, (bool) ($data['send'] ?? true));
                }, 'قیمت ثبت شد');
            });
    }

    private static function sendQuoteAction(string $name = 'send_quote'): Action
    {
        return self::workflowAction($name, 'تأیید و ارسال قیمت برای کاربر', 'heroicon-o-paper-airplane', 'success',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_QUOTING, OrderWorkflow::STAGE_REQUOTE)
                && self::workflow()->openQuote($r)?->status === OrderQuote::STATUS_PENDING_REVIEW)
            ->requiresConfirmation()
            ->modalDescription(fn (Order $record) => self::quoteSummary(self::workflow()->openQuote($record)))
            ->action(function (Order $record, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->sendQuote(self::workflow()->openQuote($record)), 'قیمت برای کاربر ارسال شد');
            });
    }

    private static function discardQuoteAction(): Action
    {
        return self::workflowAction('discard_quote', 'رد پیشنهاد قیمت', 'heroicon-o-x-mark', 'danger',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_REQUOTE)
                && self::workflow()->openQuote($r)?->status === OrderQuote::STATUS_PENDING_REVIEW)
            ->modalDescription(fn (Order $record) => self::quoteSummary(self::workflow()->openQuote($record)))
            ->form([Forms\Components\Textarea::make('reason')->label('علت رد')->required()->rows(2)->maxLength(1000)])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->discardQuote(self::workflow()->openQuote($record), $data['reason']), 'پیشنهاد قیمت رد شد');
            });
    }

    private static function decideQuoteAction(string $name = 'decide_quote'): Action
    {
        return self::workflowAction($name, 'ثبت پاسخ کاربر به قیمت', 'heroicon-o-chat-bubble-left-right', 'warning',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_QUOTING, OrderWorkflow::STAGE_REQUOTE)
                && self::workflow()->openQuote($r)?->status === OrderQuote::STATUS_SENT)
            ->modalHeading('ثبت پاسخ کاربر (تلفنی / پیام)')
            ->modalDescription(fn (Order $record) => self::quoteSummary(self::workflow()->openQuote($record)) . ' — کاربر از اپ هم می‌تواند تأیید یا رد کند.')
            ->form([
                Forms\Components\Radio::make('decision')->label('پاسخ کاربر')->options(['accept' => 'تأیید', 'reject' => 'رد'])->required()->live(),
                Forms\Components\Textarea::make('note')->label(fn (Forms\Get $get) => $get('decision') === 'reject' ? 'علت رد (الزامی)' : 'توضیحات')
                    ->required(fn (Forms\Get $get) => $get('decision') === 'reject')->rows(2)->maxLength(1000),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record,
                    fn () => self::workflow()->decideQuote(self::workflow()->openQuote($record), $data['decision'] === 'accept', $data['note'] ?? null),
                    'پاسخ کاربر ثبت شد');
            });
    }

    private static function resolveRequoteAction(): Action
    {
        return self::workflowAction('resolve_requote', 'تعیین مسیر ادامه', 'heroicon-o-arrows-right-left', 'gray',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_REQUOTE) && !self::workflow()->openQuote($r))
            ->modalDescription('اگر کاربر قیمت جدید را نپذیرد، با توجه به اقدامات انجام‌شده، وضعیت دستگاه و درخواست کاربر مسیر را تعیین کنید؛ برای لغو و عودت از «لغو سفارش» استفاده کنید (بند ۷-۷).')
            ->form([
                Forms\Components\Radio::make('path')->label('مسیر')->options([
                    'continue' => 'ادامه‌ی خدمت با شرایط تأییدشده‌ی قبلی',
                    'transfer' => 'انتقال دستگاه به شرکت برای بررسی بیشتر',
                ])->required(),
                Forms\Components\Textarea::make('note')->label('توضیحات')->rows(2)->maxLength(1000),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->resolveRejectedRequote($record, $data['path'], $data['note'] ?? null), 'مسیر سفارش تعیین شد');
            });
    }

    private static function assignTechnicianAction(): Action
    {
        return self::workflowAction('assign_technician', fn (Order $r) => $r->technician_id ? 'تخصیص مجدد تکنسین' : 'تخصیص تکنسین', 'heroicon-o-user-plus', 'primary',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_ASSIGNMENT) && $r->workflow?->assignment_status !== 'accepted')
            ->modalWidth('2xl')
            ->modalDescription(fn (Order $record) => $record->workflow?->review_status !== 'approved' ? 'اطلاعات سفارش هنوز تأیید نشده است.' : 'تکنسین‌ها به ترتیب تخصص، منطقه و ظرفیت کاری مرتب شده‌اند.')
            ->fillForm(fn (Order $record) => ['technician_id' => $record->technician_id, 'date' => $record->date, 'time' => $record->time])
            ->form([
                Forms\Components\Select::make('technician_id')
                    ->label('تکنسین میدانی')
                    ->options(fn (Order $record) => self::technicianOptions($record))
                    ->searchable()
                    ->required(),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\DatePicker::make('date')->label('تاریخ مراجعه')->jalali()->required(),
                    Forms\Components\Select::make('time')->label('بازه‌ی زمانی')
                        ->options(fn (Order $record) => Order::DISPATCH_TIME_SLOTS + (filled($record->time) && !isset(Order::DISPATCH_TIME_SLOTS[$record->time]) ? [$record->time => $record->time] : []))
                        ->required(),
                ]),
                Forms\Components\Textarea::make('note')
                    ->label('توضیحات مأموریت')
                    ->helperText('تجهیزات و ابزار موردنیاز، شرایط ویژه‌ی سفارش سازمانی و … (برای تکنسین نمایش داده می‌شود).')
                    ->rows(3)
                    ->maxLength(2000),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record,
                    fn () => self::workflow()->assignTechnician($record, (int) $data['technician_id'], $data['note'] ?? null, $data['date'] ?? null, $data['time'] ?? null),
                    'سفارش به تکنسین تخصیص یافت؛ منتظر تأیید تکنسین');
            });
    }

    private static function technicianResponseAction(): Action
    {
        return self::workflowAction('technician_response', 'ثبت پاسخ تکنسین', 'heroicon-o-hand-raised', 'warning',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_ASSIGNMENT) && $r->workflow?->assignment_status === 'pending')
            ->modalDescription('تکنسین از اپ هم می‌تواند مأموریت را تأیید یا رد کند. در صورت عدم امکان، سفارش برای تخصیص مجدد به پنل برمی‌گردد (بند ۵-۵).')
            ->form([
                Forms\Components\Radio::make('decision')->label('پاسخ')->options(['accept' => 'تأیید امکان انجام مأموریت', 'reject' => 'عدم امکان انجام مأموریت'])->required()->live(),
                Forms\Components\Textarea::make('reason')->label('علت / توضیحات')->required(fn (Forms\Get $get) => $get('decision') === 'reject')->rows(2)->maxLength(1000),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record,
                    fn () => self::workflow()->technicianResponse($record, $data['decision'] === 'accept', $data['reason'] ?? null),
                    'پاسخ تکنسین ثبت شد');
            });
    }

    private static function visitOutcomeAction(): Action
    {
        return self::workflowAction('visit_outcome', 'ثبت نتیجه‌ی بررسی در محل', 'heroicon-o-clipboard-document-list', 'primary',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_VISIT) && $r->workflow?->visit_outcome !== 'user_declined')
            ->modalWidth('2xl')
            ->form([
                Forms\Components\Radio::make('outcome')->label('نتیجه (بند ۶-۴)')->options(OrderWorkflow::VISIT_OUTCOMES)->required()->live(),
                Forms\Components\Textarea::make('note')->label('شرح نتیجه و اقدامات انجام‌شده')
                    ->required(fn (Forms\Get $get) => filled($get('outcome')) && $get('outcome') !== 'serviceable_onsite')
                    ->rows(3)->maxLength(2000),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->recordVisitOutcome($record, $data['outcome'], $data['note'] ?? null), 'نتیجه‌ی بررسی ثبت شد');
            });
    }

    private static function completeServiceAction(): Action
    {
        return self::workflowAction('complete_service', 'ثبت پایان خدمت در محل', 'heroicon-o-check-circle', 'success',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_ONSITE_SERVICE))
            ->modalWidth('2xl')
            ->fillForm(fn (Order $record) => ['service_result' => $record->technician_in_place_description])
            ->form([
                Forms\Components\Textarea::make('service_actions')->label('اقدامات انجام‌شده')->required()->rows(3)->maxLength(2000),
                Forms\Components\Textarea::make('service_parts_used')->label('قطعات مصرفی')->rows(2)->maxLength(2000),
                Forms\Components\Textarea::make('service_result')->label('نتیجه‌ی کار')->required()->rows(2)->maxLength(2000),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->completeOnsiteService($record, $data), 'پایان خدمت ثبت شد؛ سفارش برای بررسی نتیجه به مرحله‌ی پایان رفت');
            });
    }

    private static function startTransferAction(): Action
    {
        return self::workflowAction('start_transfer', 'ثبت انتقال دستگاه', 'heroicon-o-truck', 'primary',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_TRANSFER) && !$r->workflow?->transfer_started_at)
            ->modalWidth('2xl')
            ->fillForm(function (Order $record) {
                $report = $record->technician_order_report;

                return [
                    'transfer_reason' => $record->workflow?->transfer_reason,
                    'transfer_device_condition' => $report ? trim(implode(' / ', array_filter([$report->product_name, $report->product_brand, $report->product_model, $report->product_color, $report->product_serial_number]))) : null,
                    'transfer_accessories' => $report?->accessories ?? $record->delivery_reports?->accessories,
                ];
            })
            ->form([
                Forms\Components\Radio::make('transfer_method')->label('روش انتقال (بند ۹-۲)')->options(OrderWorkflow::TRANSFER_METHODS)->required()->live(),
                Forms\Components\Textarea::make('transfer_reason')->label('علت ارجاع')->required()->rows(2)->maxLength(2000),
                Forms\Components\Textarea::make('transfer_device_condition')->label('مشخصات و وضعیت ظاهری دستگاه')->required()->rows(2)->maxLength(2000),
                Forms\Components\Textarea::make('transfer_accessories')->label('لوازم همراه')->rows(2)->maxLength(2000),
                Forms\Components\Textarea::make('transfer_courier_info')->label('مشخصات مأموریت پیک و اطلاعات بسته')
                    ->visible(fn (Forms\Get $get) => $get('transfer_method') === 'courier')->required()->rows(2)->maxLength(2000),
                Forms\Components\Textarea::make('transfer_custody_note')->label('زنجیره‌ی تحویل / توضیحات')->rows(2)->maxLength(2000),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->startTransfer($record, $data), 'انتقال دستگاه ثبت شد');
            });
    }

    private static function confirmReceiptAction(): Action
    {
        return self::workflowAction('confirm_receipt', 'ثبت تحویل در شرکت و صدور رسید', 'heroicon-o-document-check', 'success',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_TRANSFER) && $r->workflow?->transfer_started_at)
            ->form([
                Forms\Components\TextInput::make('transfer_received_by')->label('مسئول دریافت')->default(fn () => auth('admin')->user()?->name)->required()->maxLength(191),
                Forms\Components\DateTimePicker::make('transfer_received_at')->label('زمان تحویل')->jalali()->default(now())->required(),
                Forms\Components\TextInput::make('transfer_receipt_number')->label('شماره رسید')->helperText('خالی بماند تا خودکار صادر شود.')->maxLength(64),
                Forms\Components\Textarea::make('transfer_custody_note')->label('زنجیره‌ی تحویل / توضیحات')->rows(2)->maxLength(2000),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, function () use ($record, $data) {
                    $receipt = self::workflow()->confirmTransferReceipt($record, $data);
                    Notification::make()->title('رسید ' . $receipt . ' صادر شد')->success()->send();
                }, 'تحویل دستگاه در شرکت ثبت شد');
            });
    }

    private static function finalPriceAction(): Action
    {
        return self::workflowAction('final_price', 'ثبت قیمت نهایی', 'heroicon-o-banknotes', 'primary',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_CLOSED) && (int) $r->status !== 2
                && in_array($r->workflow?->field_result, ['done_onsite', 'transferred_to_loop'], true))
            ->fillForm(fn (Order $record) => ['price' => $record->technician_price ?? self::workflow()->acceptedQuote($record)?->total])
            ->form([
                Forms\Components\TextInput::make('price')->label('مبلغ نهایی لوپ (بدون تخفیف و خدمات اضافی)')->numeric()->minValue(1)->suffix('تومان')->required(),
                Forms\Components\Textarea::make('note')->label('توضیحات')->rows(2)->maxLength(1000),
            ])
            ->action(function (Order $record, array $data, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, fn () => self::workflow()->recordFinalPrice($record, (int) $data['price'], $data['note'] ?? null), 'قیمت نهایی ثبت شد');
            });
    }

    private static function closeFileAction(): Action
    {
        return self::workflowAction('close_file', 'مختومه کردن پرونده', 'heroicon-o-lock-closed', 'success',
            fn (Order $r) => self::isStage($r, OrderWorkflow::STAGE_CLOSED) && (int) $r->status !== 2)
            ->requiresConfirmation()
            ->modalDescription(fn (Order $record) => ($b = self::workflow()->closeBlockers($record))
                ? 'شرایط برقرار نیست: ' . implode('، ', $b)
                : 'وضعیت سفارش «انجام شده» می‌شود و سهم تکنسین واریز می‌شود.')
            ->action(function (Order $record, Action $action, $livewire) {
                self::attempt($action, $livewire, $record, function () use ($record) {
                    $result = self::workflow()->closeFile($record);
                    if (!($result['success'] ?? false) && !in_array($result['error_code'] ?? '', ['NO_TECHNICIAN', 'ALREADY_PAID_TO_TECHNICIAN'], true)) {
                        Notification::make()->title('واریز سهم تکنسین انجام نشد')->body($result['message'] ?? null)->warning()->send();
                    }
                }, 'پرونده مختومه شد');
            });
    }

    // ───────────────────────────── سازنده‌ها ─────────────────────────────

    private static function workflowAction(string $name, string|\Closure $label, string $icon, string $color, \Closure $visible): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (Order $record) => OrderResource::canEdit($record) && $visible($record));
    }

    private static function simpleAction(string $name, string $label, string $icon, string $color, \Closure $visible, \Closure $callback, string $success): Action
    {
        return self::workflowAction($name, $label, $icon, $color, $visible)
            ->requiresConfirmation()
            ->action(function (Order $record, Action $action, $livewire) use ($callback, $success) {
                self::attempt($action, $livewire, $record, fn () => $callback($record), $success);
            });
    }

    private static function reasonAction(string $name, string $label, string $icon, string $color, array $stages, string $field,
        \Closure $callback, string $success, ?string $description = null, ?\Closure $visible = null): Action
    {
        return self::workflowAction($name, $label, $icon, $color, fn (Order $r) => self::isStage($r, ...$stages) && (!$visible || $visible($r)))
            ->modalDescription($description)
            ->form([Forms\Components\Textarea::make('note')->label($field)->required()->rows(3)->maxLength(2000)])
            ->action(function (Order $record, array $data, Action $action, $livewire) use ($callback, $success) {
                self::attempt($action, $livewire, $record, fn () => $callback($record, $data['note']), $success);
            });
    }

    /** اجرای انتقال؛ خطای فرآیند پیام می‌دهد و مودال باز می‌ماند، موفقیت صفحه را با تب مرحله‌ی جدید بازمی‌کند. */
    private static function attempt($action, $livewire, Order $record, \Closure $callback, string $success): void
    {
        try {
            $callback();
        } catch (OrderWorkflowException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $action->halt();

            return;
        }

        Notification::make()->title($success)->success()->send();
        // روی همان صفحه (ویرایش یا جزئیات) با تب مرحله‌ی جدید باز می‌شود
        $page = $livewire instanceof \App\Filament\Resources\OrderResource\Pages\ViewOrder ? 'view' : 'edit';
        $livewire->redirect(OrderResource::getUrl($page, ['record' => $record]));
    }

    // ───────────────────────────── کمکی‌ها ─────────────────────────────

    public static function workflow(): OrderWorkflowService
    {
        return app(OrderWorkflowService::class);
    }

    public static function isStage(Order $record, string ...$stages): bool
    {
        return in_array(self::workflow()->stage($record), $stages, true);
    }

    /** ۵-۲ — تکنسین‌ها به ترتیب: تخصص در دسته‌ی سفارش، همان منطقه، روشن بودن، کمترین سفارش فعال. */
    public static function technicianOptions(Order $order): array
    {
        $region = (string) ($order->user_address?->region ?? '');

        return Technician::query()
            ->where('approval_status', Technician::APPROVAL_APPROVED)
            ->where('has_access', 1)
            ->with('categories:id')
            ->withCount(['orders as active_orders_count' => fn ($q) => $q->whereNotIn('workflow_stage', [OrderWorkflow::STAGE_CLOSED, OrderWorkflow::STAGE_CANCELLED])->whereNotIn('status', [2, 3, 4, 5, 6])])
            ->get()
            ->map(fn (Technician $t) => [
                'id' => $t->id,
                'specialist' => $t->categories->contains('id', $order->category_id),
                'same_region' => $region !== '' && (string) $t->region === $region,
                'online' => (string) $t->at_work === '1',
                'load' => (int) $t->active_orders_count,
                'name' => $t->name,
                'region' => $t->region,
            ])
            ->sortBy([['specialist', 'desc'], ['same_region', 'desc'], ['online', 'desc'], ['load', 'asc']])
            ->mapWithKeys(fn (array $t) => [$t['id'] => implode(' | ', array_filter([
                $t['name'],
                $t['specialist'] ? 'متخصص این خدمت' : null,
                $t['region'] ? 'منطقه ' . $t['region'] . ($t['same_region'] ? ' (هم‌منطقه)' : '') : null,
                $t['load'] . ' سفارش فعال',
                $t['online'] ? 'روشن' : 'خاموش',
            ]))])
            ->all();
    }

    public static function quoteSummary(?OrderQuote $quote): string
    {
        if (!$quote) {
            return '';
        }

        return OrderQuote::KINDS[$quote->kind] . ' نسخه ' . $quote->version . ': اجرت ' . number_format($quote->service_fee)
            . ' + قطعات ' . number_format($quote->parts_fee) . ' + اضافی ' . number_format($quote->extra_fee)
            . ' = ' . number_format($quote->total) . ' تومان' . ($quote->is_estimate ? ' (برآورد)' : '')
            . ($quote->estimated_duration ? ' — زمان: ' . $quote->estimated_duration : '');
    }

    private static function pricingContext(Order $record): string
    {
        $type = OrderWorkflow::CUSTOMER_TYPES[$record->workflow?->customer_type] ?? '-';
        $service = OrderWorkflow::SERVICE_TYPES[$record->workflow?->service_type] ?? '-';
        $text = "نوع کاربر: {$type} — خدمت: {$service}";

        // ۴-۵ — سفارش سازمانی / شرکتی بر اساس تعرفه یا قرارداد معتبر همان کاربر
        if ($record->workflow?->customer_type !== 'individual') {
            $text .= ' — قیمت و زمان باید بر اساس تعرفه / قرارداد معتبر سازمان تعیین شود'
                . ($record->workflow?->contract_reference ? ' (قرارداد ' . $record->workflow->contract_reference . ')' : '');
        }

        return $text;
    }

    private static function paidSummary(Order $record): string
    {
        $paid = (int) \App\Models\UserTransaction::where('order_id', $record->id)->where('status', 100)->sum('price');

        return 'مبالغ دریافتی ثبت‌شده: ' . number_format($paid) . ' تومان'
            . ($record->started_at ? ' — خدمت شروع شده است؛ هزینه‌های قابل اعمال بر اساس شرایط تأییدشده‌ی کاربر بررسی شود.' : ' — هنوز خدمتی شروع نشده است.');
    }

    private static function requestedSchedule(Order $record): string
    {
        $meta = $record->stageLogs()->where('action', 'created')->value('meta');
        $date = $meta['requested_date'] ?? $record->date;
        $time = $meta['requested_time'] ?? $record->time;

        return trim(self::jd($date) . ' — ' . ($time ?? ''), ' —') ?: '-';
    }

    private static function procurementSummary(Order $record): string
    {
        return collect($record->procurement_items ?? [])
            ->map(fn ($item) => is_array($item) ? trim(($item['title'] ?? $item['name'] ?? '') . ' × ' . ($item['quantity'] ?? $item['count'] ?? 1)) : (string) $item)
            ->implode('، ');
    }

    private static function badge(string $name, string $label, array $labels, array $colors): Infolists\Components\TextEntry
    {
        return Infolists\Components\TextEntry::make($name)
            ->label($label)
            ->formatStateUsing(fn ($state) => $labels[(string) $state] ?? $state)
            ->badge()
            ->color(fn ($state) => $colors[(string) $state] ?? 'gray')
            ->placeholder('-');
    }

    public static function accountTypeLabel(?string $type): string
    {
        return match ($type) {
            'individual' => 'کاربر عادی',
            'company' => 'شرکت خصوصی',
            'organization' => 'سازمانی',
            'g_organization' => 'سازمانی دولتی',
            's_g_organization' => 'سازمانی نیمه دولتی',
            default => '-',
        };
    }

    public static function money($value): string
    {
        return $value === null || $value === '' ? '-' : number_format((float) $value) . ' تومان';
    }

    public static function jdt($value): ?string
    {
        return $value ? Jalalian::forge($value)->format('Y/m/d H:i') : null;
    }

    public static function jd($value): ?string
    {
        return $value ? Jalalian::forge($value)->format('Y/m/d') : null;
    }
}
