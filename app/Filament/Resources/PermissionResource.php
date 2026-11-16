<?php
namespace App\Filament\Resources;
use App\Services\{AdminDelegation,AdminPermissionCatalog};
use App\Traits\HasFilamentPermissions;
use Filament\{Forms,Tables};
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Spatie\Permission\Models\Permission;
class PermissionResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model=Permission::class;
    protected static ?string $navigationGroup='مدیریت کاربران';
    protected static ?string $navigationLabel='مجوزها';
    protected static ?string $modelLabel='مجوز';
    protected static ?string $pluralModelLabel='مجوزها';
    protected static ?string $navigationIcon='heroicon-o-key';
    protected static function getViewPermission(): string { return 'view-permissions'; }
    protected static function getCreatePermission(): string { return 'create-permissions'; }
    protected static function getEditPermission(): string { return 'edit-permissions'; }
    protected static function getDeletePermission(): string { return 'delete-permissions'; }
    public static function canDelete($record): bool { return AdminDelegation::can('delete-permissions') && !isset(AdminPermissionCatalog::definitions()[$record->name]) && !$record->roles()->exists() && !$record->users()->exists(); }
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('guard_name')->default('admin'),
            Forms\Components\TextInput::make('name')->label('شناسه مجوز')->regex('/^[a-z][a-z0-9-]+$/')->unique(ignoreRecord:true)->required()->disabledOn('edit')->dehydrated(fn ($operation)=>$operation==='create'),
            Forms\Components\TextInput::make('fa_name')->label('عنوان فارسی')->required()->maxLength(255),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query)=>$query->where('guard_name','admin'))->columns([
            Tables\Columns\TextColumn::make('fa_name')->label('عنوان')->searchable(),Tables\Columns\TextColumn::make('name')->label('شناسه')->searchable(),
        ])->actions([Tables\Actions\EditAction::make(),Tables\Actions\DeleteAction::make()]);
    }
    public static function getPages(): array { return ['index'=>PermissionResource\Pages\ListPermissions::route('/'),'create'=>PermissionResource\Pages\CreatePermission::route('/create'),'edit'=>PermissionResource\Pages\EditPermission::route('/{record}/edit')]; }
}
