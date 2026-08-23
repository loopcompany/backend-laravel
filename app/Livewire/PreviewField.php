<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\UserAddress;
use Livewire\Component;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Log;

class PreviewField extends Component
{

    public $des = '';
    public $date = '';
    public $time = '';
    public $address = '';
    public $pakaPrice = 0;
    public $category = '';
    public $formData = [];

    public function mount($formData, $categoryId)
    {
        $this->category = Category::findOrFail($categoryId);
        $this->des = $formData['note'][0]['value'];
        $this->date =  $formData['date'][0]['value'];
        $this->time = $formData['time'][0]['value'];

        foreach ($formData as $fieldId => $data) {
            if (!in_array($fieldId, ['date', 'time', 'address', 'note', 'file'])) {
                $price = array_reduce((array) $data, function ($carry, $item) {
                    if (is_array($item) && array_key_exists('value', $item)) {
                        return $carry + (float)($item['price'] ?? 0) * (int)$item['value'];
                    }
                    return $carry;
                }, 0);

                $this->pakaPrice += (float)$price;
            }
        }
        $this->pakaPrice = number_format($this->pakaPrice);


        if ($formData['address']['address_id']) {
            $user_address_id = $formData['address']['address_id'];
            $this->address = UserAddress::findOrFail($user_address_id)->address;
        } else {
            $this->address = $formData['address']['address'];
        }


        $this->formData['preview'] = ['value' => ''];

        $this->dispatch('formDataUpdated', [
            'key' => 'preview',
            'data' => $this->formData['preview']
        ]);
    }




    public function updatedFormData($value)
    {
        // Log::debug('*ss**', [$value] );
        $this->formData['preview'] = ['value' => $value];
        $this->dispatch('formDataUpdated', [
            'key' => 'preview',
            'data' =>  $this->formData['preview']
        ]);
    }

    public function render()
    {
        return view('livewire.preview-field');
    }
}
