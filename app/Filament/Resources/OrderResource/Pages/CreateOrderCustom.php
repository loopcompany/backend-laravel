<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\CategoryField;
use App\Models\Field;
use App\Models\OrderDetail;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Order;
use Filament\Notifications\Notification;

class CreateOrderCustom extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = OrderResource::class;

    protected static string $view = 'filament.resources.order-resource.pages.create-order-custom';


    protected static ?string $title = 'ثبت سفارش';
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data')
            ->model(Order::class);
    }

    protected function getFormSchema(): array
    {
        return [

            Forms\Components\Select::make('user_id')
                ->label('انتخاب کاربر')
                ->required()
                ->searchable()
                ->live()
                ->preload()
                ->options(
                    fn() => User::query()
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn($u) => [
                            $u->id => "{$u->name} - {$u->phone}"
                        ])
                        ->toArray()
                )
                ->getSearchResultsUsing(function (string $search) {
                    return User::query()
                        ->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn($u) => [
                            $u->id => "{$u->name} - {$u->phone}"
                        ])
                        ->toArray();
                })
                ->getOptionLabelUsing(fn($value) => User::where('id', $value)->value('name'))
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('user_address_id', null);
                    $user = User::find($state);

                    if (!$user) {
                        $set('user_name', null);
                        $set('user_phone', null);
                        return;
                    }

                    $set('user_name', $user->name);
                    $set('user_phone', $user->phone);
                }),

            Forms\Components\TextInput::make('user_name')
                ->label('نام کاربر')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\TextInput::make('user_phone')
                ->label('شماره تماس')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\Select::make('user_address_id')
                ->label('انتخاب آدرس')
                ->required()
                ->visible(fn($get) => filled($get('user_id')))
                ->options(function (callable $get) {
                    $userId = $get('user_id');
                    if (!$userId)
                        return [];

                    return UserAddress::where('user_id', $userId)
                        ->get()
                        ->mapWithKeys(fn($a) => [
                            $a->id => "{$a->title} ({$a->city}) - {$a->full_name}"
                        ]);
                })
                ->searchable()
                ->createOptionForm([
                    Forms\Components\TextInput::make('title')->label('عنوان')->required(),
                    Forms\Components\TextInput::make('fname')->label('نام')->required(),
                    Forms\Components\TextInput::make('lname')->label('نام خانوادگی')->required(),
                    Forms\Components\TextInput::make('mobile')->label('موبایل')->required(),
                    Forms\Components\TextInput::make('telephone')->label('تلفن'),
                    Forms\Components\TextInput::make('city')->label('شهر')->required(),
                    Forms\Components\TextInput::make('region')->numeric()->label('منطقه'),
                    Forms\Components\Textarea::make('address')->label('آدرس دقیق')->required(),
                ])
                ->createOptionUsing(function ($data, callable $get) {
                    $data['user_id'] = $get('user_id');
                    return UserAddress::create($data)->id;
                }),

            Forms\Components\Textarea::make('description')
                ->label('توضیحات سفارش')
                ->maxLength(191)
                ->required(),

            Forms\Components\Section::make('اطلاعات پایه سفارش')
                ->schema([

                    Forms\Components\Select::make('category_id')
                        ->label('دسته‌بندی')
                        ->options(\App\Models\Category::pluck('title', 'id')->toArray())
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('dynamic_fields', []);
                        }),

                    Forms\Components\Fieldset::make('dynamic_fields_fieldset')
                        ->label('جزئیات سفارش')
                        ->visible(fn($get) => filled($get('category_id')))
                        ->schema(function (callable $get) {
                            $categoryId = $get('category_id');
                            if (!$categoryId)
                                return [];

                            $categoryFields = CategoryField::where('category_id', $categoryId)
                                ->with(['field', 'field.field_details'])
                                ->orderBy('sort')
                                ->get();

                            $schema = [];

                            foreach ($categoryFields as $cf) {
                                $field = $cf->field;
                                if (!$field)
                                    continue;

                                $fieldId = $field->id;
                                $details = $field->field_details;
                                $key = "field_{$fieldId}";

                                // ۱. ایجاد فیلد اصلی
                                $mainField = $this->generateFieldComponent($field, $details, $key);

                                // اگر رادیو باتن بود، حتما باید live باشد برای شروط
                                if ($field->type === 'radioButton') {
                                    $mainField->live();
                                }

                                $schema[] = Forms\Components\Group::make()
                                    ->key("group_{$fieldId}")
                                    ->schema([$mainField])
                                    ->columnSpanFull();

                                // ۲. بررسی و اضافه کردن فیلدهای شرطی (Conditional)
                                $conditionals = \App\Models\CategoryFieldConditional::where('category_field_id', $cf->id)
                                    ->with(['field', 'field.field_details'])
                                    ->get();

                                foreach ($conditionals as $cond) {
                                    $condField = $cond->field;
                                    if (!$condField)
                                        continue;

                                    $condKey = "cond_field_{$condField->id}"; // کلید مجزا برای فیلد شرطی
                                    $condComponent = $this->generateFieldComponent($condField, $condField->field_details, $condKey);

                                    $schema[] = Forms\Components\Group::make()
                                        ->schema([$condComponent])
                                        ->visible(fn($get) => $get("data.dynamic_fields.{$key}.field_detail_id") == $cond->field_detail_id)
                                        ->columnSpanFull()
                                        // برای اینکه وقتی پنهان شد مقدارش پاک شود (اختیاری)
                                        ->dehydrated(fn($get) => $get("data.dynamic_fields.{$key}.field_detail_id") == $cond->field_detail_id);
                                }
                            }

                            return $schema;
                        }),

                    Forms\Components\Select::make('technician_id')
                        ->label('تکنسین')
                        ->relationship(
                            'technician',
                            'name',
                            fn($query) =>
                            $query->where('approval_status', 'approved')
                                ->where('has_access', 1)
                        )
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('فقط تکنسین‌های تایید شده نمایش داده می‌شوند'),

                    Forms\Components\Select::make('status')
                        ->label('وضعیت سفارش')
                        ->options([
                            0 => 'در انتظار',
                            1 => 'در حال انجام',
                            2 => 'انجام شده',
                            3 => 'لغو شده توسط کاربر',
                            4 => 'لغو شده توسط تکنسین',
                            5 => 'لغو شده توسط ادمین',
                            6 => 'منقضی شده',
                        ])
                        ->required()
                        ->default(0),

                    Forms\Components\Select::make('payment_status')
                        ->label('وضعیت پرداخت')
                        ->options([
                            0 => 'پرداخت نشده',
                            1 => 'پرداخت شده',
                        ])
                        ->required()
                        ->default(0),

                    Forms\Components\Select::make('is_technician_verified')
                        ->label('احراز هویت تکنسین')
                        ->options([
                            '0' => 'در انتظار',
                            '1' => 'تایید شده',
                            '2' => 'رد شده',
                        ])
                        ->default('0'),
                ])
                ->columns(2),

            Forms\Components\Section::make('مبالغ')
                ->schema([

                    Forms\Components\TextInput::make('pakar_price')
                        ->label('مبلغ پایه لوپ')
                        ->numeric()
                        ->suffix('تومان'),

                    Forms\Components\TextInput::make('technician_price')
                        ->label('مبلغ نهایی لوپ')
                        ->numeric()
                        ->suffix('تومان')
                        ->nullable(),

                    Forms\Components\TextInput::make('extra_price')
                        ->label('مبلغ قطعات و خدمات اضافی')
                        ->numeric()
                        ->suffix('تومان')
                        ->nullable(),

                    Forms\Components\TextInput::make('discount_price')
                        ->label('مبلغ تخفیف')
                        ->numeric()
                        ->suffix('تومان')
                        ->default(0),
                ])
                ->columns(2),

            Forms\Components\Section::make('زمان‌بندی')
                ->schema([

                    Forms\Components\DatePicker::make('date')
                        ->label('تاریخ سرویس')
                        ->jalali()
                        ->required(),

                    Forms\Components\TextInput::make('time')
                        ->label('ساعت سرویس')
                        ->required(),

                    Forms\Components\DateTimePicker::make('set_off_at')
                        ->label('زمان حرکت تکنسین')
                        ->jalali()
                        ->nullable(),

                    Forms\Components\DateTimePicker::make('arrived_at')
                        ->label('زمان رسیدن تکنسین')
                        ->jalali()
                        ->nullable(),

                    Forms\Components\DateTimePicker::make('send_to_loop')
                        ->label('زمان اعزام محصول به لوپ')
                        ->jalali()
                        ->nullable(),
                ])
                ->columns(2),

            Forms\Components\Section::make('اطلاعات لوپ')
                ->schema([

                    Forms\Components\TextInput::make('duration')
                        ->label('مدت زمان انجام سفارش')
                        ->numeric()
                        ->suffix('روز کاری')
                        ->nullable(),

                    Forms\Components\TextInput::make('loop_cost_estimate')
                        ->label('هزینه تقریبی لوپ')
                        ->numeric()
                        ->suffix('تومان')
                        ->nullable(),

                    Forms\Components\TextInput::make('prepayment')
                        ->label('درصد پیش پرداخت')
                        ->numeric()
                        ->suffix('%')
                        ->nullable(),

                    Forms\Components\Select::make('prepayment_payment_status')
                        ->label('پیش پرداخت')
                        ->options([
                            0 => 'پرداخت نشده',
                            1 => 'پرداخت شده',
                        ])
                        ->default(0),

                    Forms\Components\Textarea::make('loop_description')
                        ->label('توضیحات لوپ')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('حمل و نقل')
                ->schema([

                    Forms\Components\Select::make('shipment_status')
                        ->label('وضعیت اعزام محصول')
                        ->options([
                            'پیک / شخص / نماینده کاربر' => 'پیک / شخص / نماینده کاربر',
                            'پیک / تکنسین لوپ' => 'پیک / تکنسین لوپ',
                        ]),

                    Forms\Components\Textarea::make('shipment_status_descriptions')
                        ->label('توضیحات اعزام'),
                ])
                ->columns(2),

            Forms\Components\Section::make('توضیحات')
                ->schema([

                    Forms\Components\Textarea::make('des')
                        ->label('توضیحات کاربر')
                        ->rows(3),

                    Forms\Components\Textarea::make('technician_des')
                        ->label('توضیحات سفارش پس از بررسی')
                        ->rows(3)
                        ->nullable(),

                    Forms\Components\Textarea::make('technician_opinion')
                        ->label('نظر تکنسین')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\FileUpload::make('image_path')
                ->label('تصویر سفارش')
                ->image()
                ->directory('orders')
                ->visibility('public'),

            Forms\Components\TextInput::make('female_count')
                ->label('تعداد تکنسین خانم')
                ->numeric()
                ->default(0),

            Forms\Components\TextInput::make('male_count')
                ->label('تعداد تکنسین آقا')
                ->numeric()
                ->default(0),

        ];
    }

    public function create(): void
    {
        $data = $this->form->getState();

        // مقدار ثابت
        $data['platform'] = 'phone';

        // ایجاد سفارش اصلی
        $order = Order::create($data);

        // دریافت فیلدهای داینامیک
        $dynamicFields = $data['dynamic_fields'] ?? [];

        foreach ($dynamicFields as $key => $fieldData) {

            // فقط کلیدهایی مجازند که با field_ یا cond_field_ شروع شوند
            if (!str_starts_with($key, 'field_') && !str_starts_with($key, 'cond_field_')) {
                continue;
            }

            // استخراج ID فیلد (برای هر دو حالت)
            $fieldId = (int) str_replace(['field_', 'cond_field_'], '', $key);

            $field = Field::find($fieldId);
            if (!$field) {
                continue;
            }

            switch ($field->type) {

                // -------------------------
                // input & counter
                // -------------------------
                case 'input':
                case 'counter':
                    $value = $fieldData['value'] ?? null;

                    if (blank($value)) {
                        break;
                    }

                    OrderDetail::create([
                        'order_id' => $order->id,
                        'field_id' => $fieldId,
                        'field_detail_id' => null,
                        'value' => $value,
                        'price' => null,
                    ]);
                    break;

                // -------------------------
                // radio button
                // -------------------------
                case 'radioButton':
                    $detailId = $fieldData['field_detail_id'] ?? null;

                    if (empty($detailId)) {
                        break;
                    }

                    OrderDetail::create([
                        'order_id' => $order->id,
                        'field_id' => $fieldId,
                        'field_detail_id' => $detailId,
                        'value' => 1,
                        'price' => null,
                    ]);
                    break;

                // -------------------------
                // checkbox (هر detail یک فیلد جدا)
                // -------------------------
                case 'checkbox':

                    if (!is_array($fieldData)) {
                        break;
                    }

                    foreach ($fieldData as $detailKey => $isChecked) {
                        if (!str_starts_with($detailKey, 'detail_')) {
                            continue;
                        }

                        if (!$isChecked) {
                            continue;
                        }

                        $detailId = (int) str_replace('detail_', '', $detailKey);

                        OrderDetail::create([
                            'order_id' => $order->id,
                            'field_id' => $fieldId,
                            'field_detail_id' => $detailId,
                            'value' => 1,
                            'price' => null,
                        ]);
                    }
                    break;
            }
        }

        // اعلان موفقیت
        Notification::make()
            ->title('سفارش با موفقیت ثبت شد')
            ->success()
            ->send();

        // ریدایرکت
        $this->redirect(OrderResource::getUrl('index'));
    }

    protected function generateFieldComponent($field, $details, $key): Forms\Components\Component
    {
        return match ($field->type) {
            'input' => Forms\Components\TextInput::make("dynamic_fields.{$key}.value")
                ->label($field->title)
                ->required((bool) $field->is_required),

            'counter' => Forms\Components\TextInput::make("dynamic_fields.{$key}.value")
                ->label($field->title)
                ->numeric()
                ->required((bool) $field->is_required),

            'checkbox' => Forms\Components\Section::make($field->title)
                ->schema(function () use ($details, $key) {
                        $checkboxes = [];
                        foreach ($details as $detail) {
                            $checkboxes[] = Forms\Components\Checkbox::make("dynamic_fields.{$key}.detail_{$detail->id}")
                            ->label($detail->title)
                            ->inline(false);
                        }
                        return $checkboxes;
                    })
                ->columns(2)
                ->compact(),

            'radioButton' => Forms\Components\Radio::make("dynamic_fields.{$key}.field_detail_id")
                ->label($field->title)
                ->options($details->pluck('title', 'id')->toArray())
                ->required((bool) $field->is_required),

            default => Forms\Components\TextInput::make("dynamic_fields.{$key}.value")
                ->label($field->title),
        };
    }
}
