<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'سمت‌ها';

    protected static ?string $modelLabel = 'سمت';

    protected static ?string $pluralModelLabel = 'سمت‌ها';

    // فقط سوپر ادمین سمت‌ها را مدیریت کند
    public static function canViewAny(): bool
    {
        return auth('admin')->user()?->hasRole('super-admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth('admin')->user()?->hasRole('super-admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth('admin')->user()?->hasRole('super-admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth('admin')->user()?->hasRole('super-admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات سمت')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('نام سمت')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('guard_name')
                            ->label('نوع گارد')
                            ->options([
                                'admin' => 'Admin',
                                'web' => 'Web',
                            ])
                            ->default('admin')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('مجوزها')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('انتخاب مجوزها')
                            ->relationship('permissions', 'name')
                            ->options(Permission::where('guard_name', 'admin')->pluck('fa_name', 'id'))
                            ->searchable()
                             
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام سمت')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->colors([
                        'danger' => 'super-admin',
                        'warning' => 'admin',
                        'primary' => 'moderator',
                    ]),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('نوع گارد')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('تعداد مجوزها')
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('تعداد کاربران')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->label('نوع گارد')
                    ->options([
                        'admin' => 'Admin',
                        'web' => 'Web',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('ویرایش'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn($record) => $record->name !== 'super-admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف انتخاب شده‌ها'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
