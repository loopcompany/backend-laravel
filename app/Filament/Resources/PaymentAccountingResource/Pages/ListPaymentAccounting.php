<?php
namespace App\Filament\Resources\PaymentAccountingResource\Pages;
class ListPaymentAccounting extends \Filament\Resources\Pages\ListRecords
{
    protected static string $resource=\App\Filament\Resources\PaymentAccountingResource::class;
    protected function getHeaderActions(): array { return []; }
}
