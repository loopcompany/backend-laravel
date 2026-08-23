<?php

namespace App\Livewire;

use App\Models\Field;
use App\Models\FieldDetail;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CheckboxField extends Component
{
    public $field;
    public $formData = [];
    public $mainData = [];

    public function mount($field, $mainData)
    {
        $this->field = $field;
        $this->mainData = $mainData;
        $this->initializeFormData();
        $this->dispatchFormData();
    }

    /**
     * Initialize form data for checkbox field
     */
    private function initializeFormData(): void
    {
        if (!is_iterable($this->field->field_details)) {
            return;
        }

        $this->formData[$this->field->id] = [];

        foreach ($this->field->field_details as $detail) {
            $savedValue = $this->getSavedValue($detail->id);
            
            // Use saved value if available, otherwise use default is_checked value
            $value = $savedValue > 0 ? $savedValue : (int)$detail->is_checked;

            $this->formData[$this->field->id][] = [
                'detail_id' => $detail->id,
                'value' => $value,
                'price' => $detail->price ?? 0
            ];
        }
    }

    /**
     * Get saved value for a specific detail
     */
    private function getSavedValue(int $detailId): int
    {
        if (empty($this->mainData[$this->field->id])) {
            return 0;
        }

        $key = array_search($detailId, array_column($this->mainData[$this->field->id], 'detail_id'));
        return ($key !== false) ? (int)$this->mainData[$this->field->id][$key]['value'] : 0;
    }

    /**
     * Update checkbox state
     */
    public function updateCheckbox(int $fieldId, int $detailId, bool $checked): void
    {
        $index = $this->findDetailIndex($detailId);
        
        if ($index !== false) {
            $currentValue = $this->formData[$fieldId][$index]['value'];
            $this->formData[$fieldId][$index]['value'] = $checked ? max(1, $currentValue) : 0;
            $this->dispatchFormData();
        }
    }

    /**
     * Increment counter for checkbox
     */
    public function increment(int $fieldId, int $detailId): void
    {
        $index = $this->findDetailIndex($detailId);
        
        if ($index !== false) {
            $this->formData[$fieldId][$index]['value']++;
            $this->dispatchFormData();
        }
    }

    /**
     * Decrement counter for checkbox
     */
    public function decrement(int $fieldId, int $detailId): void
    {
        $index = $this->findDetailIndex($detailId);
        
        if ($index !== false && $this->formData[$fieldId][$index]['value'] > 1) {
            $this->formData[$fieldId][$index]['value']--;
            $this->dispatchFormData();
        }
    }

    /**
     * Check if a specific checkbox is checked
     */
    public function is_checked(int $fieldId, int $detailId): bool
    {
        $index = $this->findDetailIndex($detailId);
        return ($index !== false && $this->formData[$fieldId][$index]['value'] > 0);
    }

    /**
     * Get current value for a specific detail
     */
    public function getCurrentValue(int $detailId): int
    {
        $index = $this->findDetailIndex($detailId);
        return ($index !== false) ? $this->formData[$this->field->id][$index]['value'] : 0;
    }

    /**
     * Find index of detail in form data array
     */
    private function findDetailIndex(int $detailId): int|false
    {
        return array_search($detailId, array_column($this->formData[$this->field->id] ?? [], 'detail_id'));
    }

    /**
     * Dispatch form data to parent component
     */
    private function dispatchFormData(): void
    {
        $this->dispatch('formDataUpdated', [
            'key' => $this->field->id,
            'data' => $this->formData[$this->field->id] ?? []
        ]);
    }

    public function render()
    {
        return view('livewire.checkbox-field');
    }
}
