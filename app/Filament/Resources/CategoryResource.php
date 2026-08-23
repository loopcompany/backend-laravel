<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Filament\Resources\CategoryResource\RelationManagers\ApplicationSliderRelationManager;
use App\Filament\Resources\CategoryResource\RelationManagers\CategoryFieldsRelationManager;
use App\Filament\Resources\CategoryResource\RelationManagers\CategoryLabelsRelationManager;
use App\Filament\Resources\CategoryResource\RelationManagers\CategoryRelationManager;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\Category;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Repeater;
use App\Traits\HasFilamentPermissions;


class CategoryResource extends Resource
{
    use HasFilamentPermissions;
    
    protected static ?string $model = Category::class;

    protected static ?string $navigationGroup = 'مدیریت دسته‌بندی و فرمساز';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'دسته‌بندی‌ها';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'دسته‌بندی';
    protected static ?string $modelLabel = 'دسته‌بندی';
    protected static ?string $pluralModelLabel  = 'دسته‌بندی‌ها';

    protected static function getViewPermission(): string
    {
        return 'view-categories';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-categories';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-categories';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-categories';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('نام دسته‌بندی')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('لینک کوتاه و قابل خواندن برای URL. به انگلیسی باشد.'),

                Forms\Components\Select::make('parent_id')
                    ->label('دسته‌بندی والد')
                     ->options(function ($get, $record) {
                            $query = \App\Models\Category::where('has_subcategory', 1);
                    
                            if ($record && $record->id) {
                                $query->where('id', '!=', $record->id);
                            }
                    
                            return $query->get()->mapWithKeys(function ($category) {
                                return [$category->id => $category->breadcrumb_title];
                            });
                        })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->reactive(),

                Forms\Components\Select::make('target_type')
                    ->label('نوع کاربری')
                    ->options([
                        'user' => '👤 کاربر',
                        'organization' => '🏢 سازمان',
                        'both' => '🌐 هردو',
                    ])
                    ->default('both')
                    ->required()
                    ->reactive()
                    ->rules([
                        function ($get, $record) {
                            return function ($attribute, $value, $fail) use ($get, $record) {
                                $parentId = $get('parent_id');
                                
                                // اگر والد دارد، بررسی تطابق با والد
                                if ($parentId) {
                                    $parent = \App\Models\Category::find($parentId);
                                    if ($parent) {
                                        $parentTargetType = $parent->target_type;
                                        
                                        // اگر والد both نیست و فرزند با والد یکسان نیست
                                        if ($parentTargetType !== 'both' && $value !== $parentTargetType) {
                                            $parentLabel = [
                                                'user' => 'کاربر',
                                                'organization' => 'سازمان',
                                            ][$parentTargetType];
                                            
                                            $fail("دسته‌بندی والد از نوع {$parentLabel} است. فرزند باید همان نوع باشد.");
                                        }
                                    }
                                }
                                
                                // اگر در حال ویرایش است، بررسی فرزندان
                                if ($record && $record->id) {
                                    $result = $record->canChangeTargetTypeTo($value);
                                    if (!$result['can']) {
                                        $fail($result['message']);
                                    }
                                }
                            };
                        },
                    ])
                    ->helperText(function ($get) {
                        $parentId = $get('parent_id');
                        if ($parentId) {
                            $parent = \App\Models\Category::find($parentId);
                            if ($parent) {
                                $parentLabel = [
                                    'user' => 'کاربر',
                                    'organization' => 'سازمان',
                                    'both' => 'هردو',
                                ][$parent->target_type];
                                
                                if ($parent->target_type === 'both') {
                                    return "✅ والد از نوع 'هردو' است - می‌توانید هر گزینه‌ای را انتخاب کنید.";
                                } else {
                                    return "⚠️ والد از نوع '{$parentLabel}' است - فرزند باید همان نوع باشد.";
                                }
                            }
                        }
                        return 'نوع کاربرانی که می‌توانند از این دسته‌بندی استفاده کنند.';
                    }),

                Forms\Components\FileUpload::make('image_path')
                    ->label('تصویر دسته‌بندی')
                    ->directory('category')
                    ->image()
                    ->maxSize(2048) // حداکثر حجم 2MB
                    ->required(),

                Forms\Components\ToggleButtons::make('has_subcategory')
                    ->label('دارای زیرمجموعه؟')
                    ->options([
                        0 => '❌ ندارد',
                        1 => '✅ دارد',
                    ])
                    ->inline()
                    ->required(),
                    
                Forms\Components\ToggleButtons::make('has_gender')
                    ->label('جنسیت تکنسین')
                    ->options([
                        0 => '❌ ندارد',
                        1 => '✅ دارد',
                    ])
                    ->inline()
                     ->hidden(function ($component) {
                            // Hide on create
                            if (request()->routeIs('filament.resources.your-resource.create')) {
                                return true;
                            }
                            
                            // Hide if `hs_subcategory` is not 1
                            return $component->getRecord()?->has_subcategory == 1;
                        })
                    ->required(),
                Forms\Components\ToggleButtons::make('is_fixed')
                    ->label('قیمت قطعی')
                    ->options([
                        0 => '❌ ندارد',
                        1 => '✅ دارد',
                    ])
                    ->inline()
                     ->hidden(function ($component) {
                            // Hide on create
                            if (request()->routeIs('filament.resources.your-resource.create')) {
                                return true;
                            }
                            
                            // Hide if `hs_subcategory` is not 1
                            return $component->getRecord()?->has_subcategory == 1;
                        })
                    ->required(),

                Forms\Components\TimePicker::make('start_at')
                    ->label('شروع از ساعت')
                    ->required()
                    ->default('06:00'),

                Forms\Components\TimePicker::make('end_at')
                    ->label('تا ساعت')
                    ->required()
                    ->default('00:00'),

                Forms\Components\TextInput::make('duration')
                    ->label('مدت زمان (دقیقه)')
                    ->numeric()
                    ->required()
                    ->default(60),
                       // =====================
            // فیلدهای جدید سئو و متن پایین کارت
            // =====================
            // TinyEditor::make('seo_content')->showMenuBar()->toolbarSticky(true)
            //     ->label('متن پایین کارت خدمات')
            //     ->columnSpanFull(),

            Forms\Components\TextInput::make('meta_title')
                ->label('Meta Title')
                ->maxLength(70)
                ->helperText('برای SEO. حداکثر 70 کاراکتر.'),

            Forms\Components\Textarea::make('meta_description')
                ->label('Meta Description')
                ->maxLength(160)
                ->helperText('برای SEO. حداکثر 160 کاراکتر.'),

            Forms\Components\Repeater::make('faq_schema')
                ->label('FAQ Schema')
                ->schema([
                    Forms\Components\TextInput::make('question')
                        ->label('سوال')
                        ->required(),
                    Forms\Components\TextInput::make('answer')
                        ->label('پاسخ')
                        ->required(),
                ])
                ->columnSpanFull()
                ->default([]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->query(Category::query()->whereNull('parent_id'))
            ->columns([
                Tables\Columns\TextColumn::make('sort')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('ترتیب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('شناسه')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('نام دسته‌بندی')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.title')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('دسته‌بندی والد')
                    ->sortable()
                    ->default('—'),

                Tables\Columns\BadgeColumn::make('target_type')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('نوع کاربری')
                    ->formatStateUsing(function ($state) {
                        return [
                            'user' => '👤 کاربر',
                            'organization' => '🏢 سازمان',
                            'both' => '🌐 هردو',
                        ][$state] ?? 'ناشناس';
                    })
                    ->colors([
                        'user' => 'success',
                        'organization' => 'warning',
                        'both' => 'info',
                    ]),

                Tables\Columns\ImageColumn::make('image_path')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('تصویر')
                    ->size(50),

                Tables\Columns\BadgeColumn::make('has_subcategory')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('نوع دسته‌بندی')
                    ->formatStateUsing(function ($state) {
                        return [
                            0 => 'دارای خدمت',
                            1 => 'دارای زیردسته',
                        ][$state] ?? 'ناشناس';
                    })
                    ->colors([
                        0 => 'primary',
                        1 => 'info',
                    ]),

                Tables\Columns\TextColumn::make('start_at')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('شروع')
                    ->time(),

                Tables\Columns\TextColumn::make('end_at')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('پایان')
                    ->time(),

                Tables\Columns\TextColumn::make('duration')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('مدت (دقیقه)'),


                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('تاریخ ایجاد')
                    ->sortable()
                    ->jalaliDate(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return  [
            CategoryRelationManager::class,
            CategoryFieldsRelationManager::class,
            // ApplicationSliderRelationManager::class,
            // CategoryLabelsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ordered();
    }

    
}
