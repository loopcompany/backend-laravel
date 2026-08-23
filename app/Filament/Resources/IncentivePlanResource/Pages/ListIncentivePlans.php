<?php

namespace App\Filament\Resources\IncentivePlanResource\Pages;

use App\Filament\Resources\IncentivePlanResource;
use App\Models\IncentivePlan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListIncentivePlans extends ListRecords
{
    protected static string $resource = IncentivePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('ایجاد طرح تشویقی جدید')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('همه')
                ->badge(IncentivePlan::count())
                ->badgeColor('gray'),
            
            'active' => Tab::make('فعال')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', IncentivePlan::STATUS_PENDING))
                ->badge(IncentivePlan::where('status', IncentivePlan::STATUS_PENDING)->count())
                ->badgeColor('success'),
            
            'used' => Tab::make('استفاده شده')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', IncentivePlan::STATUS_USED))
                ->badge(IncentivePlan::where('status', IncentivePlan::STATUS_USED)->count())
                ->badgeColor('info'),
            
            'expired' => Tab::make('منقضی شده')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', IncentivePlan::STATUS_EXPIRED))
                ->badge(IncentivePlan::where('status', IncentivePlan::STATUS_EXPIRED)->count())
                ->badgeColor('danger'),
        ];
    }
}

