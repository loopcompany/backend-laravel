<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

class InputField extends Component
{
    public $field;
    public $mainData = [];
    public $formData = [];

    public function mount($field, $mainData = [])
    {
        $this->field = $field;
        $this->mainData = $mainData;
        $this->initializeFormData();
        $this->dispatchFormData();
    }

    /**
     * Initialize form data for input fields
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
        }
    }

    /**
     * Get saved value for a specific detail
     */
    private function getSavedValue(int $detailId): string
    {
        if (empty($this->mainData[$this->field->id])) {
            return '';
        }

        foreach ($this->mainData[$this->field->id] as $detailData) {
            if ($detailData['detail_id'] == $detailId) {
                return $detailData['value'] ?? '';
            }
        }

        return '';
    }

    /**
     * Update form data when input value changes
     */
    public function updateValue(int $detailId, string $value): void
    {
        $index = $this->findDetailIndex($detailId);
        
        if ($index !== false) {
            $this->formData[$this->field->id][$index]['value'] = trim($value);
            $this->dispatchFormData();
        }
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
        return view('livewire.input-field');
    }
}