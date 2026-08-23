<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogResource\Pages;
use App\Filament\Resources\BlogResource\RelationManagers;
use App\Filament\Resources\BlogResource\RelationManagers\TagsRelationManager;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\Blog;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'مقالات';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'مقالات';
    protected static ?string $modelLabel = 'مقالات';
    protected static ?string $pluralModelLabel  = 'مقالات';

    // کنترل دسترسی جزئی
    public static function canViewAny(): bool
    {
        return auth('admin')->user()?->can('view-blogs') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth('admin')->user()?->can('create-blogs') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth('admin')->user()?->can('edit-blogs') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth('admin')->user()?->can('delete-blogs') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(100)
                    ->label('موضوع'),

                Forms\Components\Select::make('category_id')
                    ->options(fn() => Category::all()->pluck('title', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('دسته بندی'),

                Forms\Components\FileUpload::make('image_path')
                    ->directory('blog')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->required()
                    ->label('تصویر'),

                Forms\Components\FileUpload::make('audio_path')
                    ->directory('blog')
                    ->acceptedFileTypes(['audio/ogg', 'audio/mpeg'])
                    ->maxSize(10240)
                    ->label('توضیحات صوتی'),

                Forms\Components\FileUpload::make('video_path')
                    ->directory('blog')
                    ->acceptedFileTypes(['video/mp4', 'video/mov', 'video/avi'])
                    ->maxSize(10240)
                    ->label('ویدیو'),

                Forms\Components\FileUpload::make('document_path')
                    ->directory('workout')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024)
                    ->nullable()
                    ->downloadable()
                    ->openable()
                    ->label('فایل PDF'),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(150)
                    ->label('Slug انگلیسی')
                    ->helperText('این اسلاگ در URL استفاده می‌شود و بهتر است بدون فاصله و با حروف انگلیسی باشد'),

                Forms\Components\RichEditor::make('short_des')
                    ->required()
                    ->columnSpanFull()
                    ->label('توضیحات کوتاه'),

                RichEditor::make('des')
                    ->columnSpanFull()
                    ->required()
                    ->label('توضیحات'),

                // --- SEO Fields ---
                Forms\Components\TextInput::make('seo_title')
                    ->maxLength(70)
                    ->label('عنوان سئو')
                    ->helperText('حداکثر 70 کاراکتر برای عنوان سئو'),

                Forms\Components\Textarea::make('meta_description')
                    ->maxLength(320)
                    ->label('متا دسکریپشن')
                    ->helperText('حداکثر 320 کاراکتر برای توضیحات متا'),

                // --- FAQ Section ---
                Forms\Components\Repeater::make('faqs')
                    ->label('FAQ')
                    ->collapsible()
                     ->collapsed() // به‌صورت پیش‌فرض بسته است
                    ->createItemButtonLabel('سوال جدید')
                    ->schema([
                        Forms\Components\TextInput::make('question')
                            ->required()
                            ->label('سوال'),
                        Forms\Components\RichEditor::make('answer')
                            ->required()
                            ->label('پاسخ'),
                            
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label('تصویر'),
                Tables\Columns\TextColumn::make('title')->searchable()->label('موضوع'),
                Tables\Columns\TextColumn::make('category.title')->sortable()->label('دسته بندی'),
                Tables\Columns\TextColumn::make('slug')->searchable()->sortable()->label('Slug'),
                Tables\Columns\TextColumn::make('short_des')->sortable()->html()->words(7)->label('توضیحات کوتاه'),
                Tables\Columns\TextColumn::make('created_at')->sortable()->jalaliDate()->toggleable(isToggledHiddenByDefault: true)->label('تاریخ ثبت'),
                Tables\Columns\TextColumn::make('updated_at')->sortable()->jalaliDate()->toggleable(isToggledHiddenByDefault: true)->label('تاریخ ویرایش'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
        return [
            // TagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'edit' => Pages\EditBlog::route('/{record}/edit'),
        ];
    }

}
