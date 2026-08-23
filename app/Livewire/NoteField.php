<?php

namespace App\Livewire;

use Livewire\Component;

class NoteField extends Component
{
    public $field;
    public $formData = [];
    public $content = '';

    public function mount($field)
    {
        $this->field = $field;
        $this->formData['note'] = ['value' => ''];

        $this->dispatch('formDataUpdated', [
            'key' => 'note',
            'data' => $this->formData['note']
        ]);
    }

    public function updatedFormData($value)
    {
        $this->formData['note'] = ['value' => $value];
        $this->dispatch('formDataUpdated', [
            'key' => 'note',
            'data' =>  $this->formData['note']
        ]);
    }

    public function render()
    {
        return view('livewire.note-field');
    }
}
