<?php

namespace App\Repositories;

use App\Models\GemAction;
use Illuminate\Database\Eloquent\Collection;

class GemActionRepository
{
    /**
     * دریافت لیست تمام gem action های فعال
     */
    public function getActiveActions(): Collection
    {
        return GemAction::where('is_active', true)
            ->orderBy('gems', 'desc')
            ->get();
    }

    /**
     * دریافت همه gem action ها
     */
    public function getAllActions(): Collection
    {
        return GemAction::orderBy('gems', 'desc')->get();
    }

    /**
     * پیدا کردن gem action با action_key
     */
    public function findByActionKey(string $actionKey): ?GemAction
    {
        return GemAction::where('action_key', $actionKey)->first();
    }

    /**
     * پیدا کردن gem action با مقدار gem
     */
    public function findByGems(int $gems): ?GemAction
    {
        return GemAction::where('gems', $gems)->first();
    }

    /**
     * پیدا کردن gem action با ID
     */
    public function findById(int $id): ?GemAction
    {
        return GemAction::find($id);
    }
}
