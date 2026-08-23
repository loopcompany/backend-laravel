<?php

namespace App\Livewire;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class DateField extends Component
{
    public $field;
    public $isUrgent;
    public $dateOptions = [];
    public $formData = [];


    public function mount($field, $isUrgent = false)
    {
        $this->field = $field;
        $this->isUrgent = $isUrgent;
        $this->initializeFormData();
        $this->generateDates();
    }

    /**
     * Initialize form data structure
     */
    private function initializeFormData(): void
    {
        $this->formData['date'] = [['value' => '']];
        $this->dispatchFormData();
    }

    /**
     * Handle date selection
     */
    public function radioSelected(string $value): void
    {
        $this->formData['date'][0]['value'] = $value;
        $this->dispatchFormData();
    }

    /**
     * Generate available dates for next 20 days
     */
    public function generateDates(): void
    {
        $this->dateOptions = collect(range(0, 19))->map(function ($i) {
            return Jalalian::now()->addDays($i)->format('Y/m/d');
        });
    }

    /**
     * Dispatch form data to parent component
     */
    private function dispatchFormData(): void
    {
        $this->dispatch('formDataUpdated', [
            'key' => 'date',
            'data' => $this->formData['date']
        ]);
    }

    public function render()
    {
        return view('livewire.date-field');
    }
}
