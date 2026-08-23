<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

class UrgentField extends Component
{
    public $field;
    public $formData = [];

    public function mount($field)
    {
        $this->field = $field;
        $this->formData['urgent'] = ['value' => false];
        $this->dispatch('formDataUpdated', [
            'key' => 'urgent',
            'data' => $this->formData['urgent']
        ]);
    }

    public function updateCheckbox($value)
    {
        // Log::debug('urgent field: ', [$value]);
        $this->formData['urgent']['value'] = $value;
        $this->dispatch('formDataUpdated', [
            'key' => 'urgent',
            'data' => $this->formData['urgent']
        ]);
        // $this->dispatch('urgentToggled', $value);
    }
    public function render()
    {
        return view('livewire.urgent-field');
    }
}
