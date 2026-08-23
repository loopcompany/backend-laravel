<?php

namespace App\Repositories;

use App\Models\CategoryField;
use App\Models\CategoryFieldConditional;

class StepRepository
{
    public function getCategoryFieldsGrouped(int $categoryId)
    {
        return CategoryField::with([
                'field.field_details.field_charts.chart_options',
                'category:id,has_gender'
            ])
            ->where('category_id', $categoryId)
            ->orderBy('step', 'asc')
            ->orderBy('sort', 'asc')
            ->get()
            ->groupBy('step');
    }

    public function getConditionalFieldsGrouped(int $categoryId, int $fieldId, int $fieldDetailId)
    {
        $categoryField = CategoryField::where('field_id', $fieldId)
            ->where('category_id', $categoryId)
            ->where('is_conditional', '1')
            ->first();

        if (!$categoryField) {
            return collect();
        }

        return CategoryFieldConditional::with(['field.field_details'])
            ->where('field_detail_id', $fieldDetailId)
            ->where('category_field_id', $categoryField->id)
            ->orderBy('step', 'asc')
            ->get()
            ->groupBy('step');
    }
}
