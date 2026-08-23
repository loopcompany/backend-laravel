<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

class RadioField extends Component
{
    public $field;
    public $formData = [];
    public $mainData = [];
    public $selectedValue = null;

    public function mount($field, $mainData)
    {
        $this->field = $field;
        $this->mainData = $mainData;
        $this->initializeFormData();
        $this->dispatchFormData();
    }

    /**
     * Initialize form data for radio field
     */
    private function initializeFormData(): void
    {
        $this->formData[$this->field->id] = [];

        foreach ($this->field->field_details as $detail) {
            $savedValue = $this->getSavedValue($detail->id);
            
            $this->formData[$this->field->id][] = [
                'detail_id' => $detail->id,
                'value' => $savedValue,
                'price' => $detail->price ?? 0
            ];

            // Set selected value if this option was previously selected
            if ($savedValue > 0) {
                $this->selectedValue = $detail->id;
            }
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
        return ($key !== false && $this->mainData[$this->field->id][$key]['value'] > 0) 
            ? (int)$this->mainData[$this->field->id][$key]['value'] 
            : 0;
    }

    /**
     * Handle radio button selection
     */
    public function radioSelected(int $fieldId, int $detailId): void
    {
        // Reset all values to 0
        foreach ($this->formData[$fieldId] as &$item) {
            $item['value'] = 0;
        }
        unset($item);

        // Set selected option to 1
        $index = $this->findDetailIndex($detailId);
        if ($index !== false) {
            $this->formData[$fieldId][$index]['value'] = 1;
            $this->selectedValue = $detailId;
        }

        $this->dispatchFormData();
        $this->dispatch('radioSelected', fieldId: $fieldId, value: $detailId);
    }

    /**
     * Increment counter for selected radio option
     */
    public function increment(int $fieldId, int $detailId): void
    {
        $index = $this->findDetailIndex($detailId);
        if ($index !== false) {
            $this->formData[$fieldId][$index]['value']++;
            $this->selectedValue = $detailId;
            $this->resetOtherOptions($fieldId, $detailId);
            $this->dispatchFormData();
            $this->dispatch('radioSelected', fieldId: $fieldId, value: $detailId);
        }
    }

    /**
     * Decrement counter for selected radio option
     */
    public function decrement(int $fieldId, int $detailId): void
    {
        $index = $this->findDetailIndex($detailId);
        if ($index !== false && $this->formData[$fieldId][$index]['value'] > 1) {
            $this->formData[$fieldId][$index]['value']--;
            $this->dispatchFormData();
            $this->dispatch('radioSelected', fieldId: $fieldId, value: $detailId);
        }
    }

    /**
     * Check if a specific option is selected
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
     * Reset all other options except the selected one
     */
    private function resetOtherOptions(int $fieldId, int $selectedDetailId): void
    {
        foreach ($this->formData[$fieldId] as &$item) {
            if ($item['detail_id'] !== $selectedDetailId) {
                $item['value'] = 0;
            }
        }
        unset($item);
    }

    /**
     * Find index of detail in form data array
     */
    private function findDetailIndex(int $detailId): int|false
    {
        return array_search($detailId, array_column($this->formData[$this->field->id], 'detail_id'));
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
        return view('livewire.radio-field');
    }
}
