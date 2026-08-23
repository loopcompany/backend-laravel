<?php

namespace App\Filament\Resources\PollApplicationResource\Pages;

use App\Filament\Resources\PollApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPollApplications extends ListRecords
{
    protected static string $resource = PollApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('همه')
                ->badge($this->getModel()::count()),
                
            'excellent' => Tab::make('امتیاز عالی')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('app_rate', 'خوب')
                    ->where('tech_rate', 'خوب')
                    ->where('support_rate', 'خوب'))
                ->badge($this->getModel()::where('app_rate', 'خوب')
                    ->where('tech_rate', 'خوب')
                    ->where('support_rate', 'خوب')
                    ->count()),
                    
            'good' => Tab::make('امتیاز خوب')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('app_rate', 'خوب')
                      ->orWhere('tech_rate', 'خوب')
                      ->orWhere('support_rate', 'خوب');
                }))
                ->badge($this->getModel()::where(function ($q) {
                    $q->where('app_rate', 'خوب')
                      ->orWhere('tech_rate', 'خوب')
                      ->orWhere('support_rate', 'خوب');
                })->count()),
                
            'poor' => Tab::make('امتیاز ضعیف')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('app_rate', 'ضعیف')
                      ->orWhere('tech_rate', 'ضعیف')
                      ->orWhere('support_rate', 'ضعیف');
                }))
                ->badge($this->getModel()::where(function ($q) {
                    $q->where('app_rate', 'ضعیف')
                      ->orWhere('tech_rate', 'ضعیف')
                      ->orWhere('support_rate', 'ضعیف');
                })->count()),
                
            'with_comments' => Tab::make('دارای نظر')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('description')
                    ->where('description', '!=', ''))
                ->badge($this->getModel()::whereNotNull('description')
                    ->where('description', '!=', '')
                    ->count()),
        ];
    }
}