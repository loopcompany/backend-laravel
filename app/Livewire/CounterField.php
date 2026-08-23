<?php

namespace App\Livewire;

use Livewire\Component;

class CounterField extends Component
{
    public $field;
    public $formData = [];
    public $mainData = [];

    // Gender options constant
    private const GENDER_OPTIONS = [
        ['id' => 'female', 'title' => 'زن', 'price' => 0],
        ['id' => 'male', 'title' => 'مرد', 'price' => 0],
        ['id' => 'any', 'title' => 'فرقی نمی‌کند', 'price' => 0],
    ];

    public function mount($field, $mainData)
    {
        $this->field = $field;
        $this->mainData = $mainData;
        $this->initializeFormData();
        $this->dispatchFormData();
    }

    /**
     * Initialize form data based on field type
     */
    private function initializeFormData(): void
    {
        $this->formData[$this->field->id] = [];

        if ($this->isGenderField()) {
            $this->initializeGenderField();
        } else {
            $this->initializeRegularField();
        }
    }

    /**
     * Check if this is a gender field
     */
    private function isGenderField(): bool
    {
        return $this->field->id === 'gender';
    }

    /**
     * Initialize gender field with predefined options
     */
    private function initializeGenderField(): void
    {
        foreach (self::GENDER_OPTIONS as $option) {
            $savedValue = $this->getSavedValue($option['id']);
            
            $this->formData[$this->field->id][] = [
                'detail_id' => $option['id'],
                'value' => $savedValue,
                'price' => $option['price']
            ];
        }
    }

    /**
     * Initialize regular counter field
     */
    private function initializeRegularField(): void
    {
        if (!is_iterable($this->field->field_details)) {
            return;
        }

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
    private function getSavedValue(string|int $detailId): int
    {
        if (empty($this->mainData[$this->field->id])) {
            return 0;
        }

        $key = array_search($detailId, array_column($this->mainData[$this->field->id], 'detail_id'));
        return ($key !== false) ? (int)$this->mainData[$this->field->id][$key]['value'] : 0;
    }

    /**
     * Update counter value
     */
    public function updateCounter(string|int $fieldId, string|int $detailId, int $value): void
    {
        $index = $this->findDetailIndex($detailId);
        
        if ($index !== false) {
            $this->formData[$fieldId][$index]['value'] = max(0, $value);
            $this->dispatchFormData();
        }
    }

    /**
     * Increment counter
     */
    public function increment(string|int $fieldId, string|int $detailId): void
    {
        $currentValue = $this->getCurrentValue($detailId);
        $this->updateCounter($fieldId, $detailId, $currentValue + 1);
    }

    /**
     * Decrement counter
     */
    public function decrement(string|int $fieldId, string|int $detailId): void
    {
        $currentValue = $this->getCurrentValue($detailId);
        if ($currentValue > 0) {
            $this->updateCounter($fieldId, $detailId, $currentValue - 1);
        }
    }

    /**
     * Get current value for a specific detail
     */
    public function getCurrentValue(string|int $detailId): int
    {
        $index = $this->findDetailIndex($detailId);
        return ($index !== false) ? $this->formData[$this->field->id][$index]['value'] : 0;
    }

    /**
     * Get gender options for template
     */
    public function getGenderOptions(): array
    {
        return self::GENDER_OPTIONS;
    }

    /**
     * Find index of detail in form data array
     */
    private function findDetailIndex(string|int $detailId): int|false
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
        return view('livewire.counter-field');
    }
}
