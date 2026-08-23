<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AddressField extends Component
{
    public $mapLoaded = false;
    public $useOldAddress = 1;
    public $selectedAddressId;
    public $formDataNEW = [];
    public $field;
    public $cityName;
    public $mainData = [];
    
    
    public $formData = [
        'address' => [
            'title' => '',
            'address' => '',
            'region_id' => '',
            'latLng' => '',
            'address_id' => null,
        ]
    ];

    public $oldAddresses;
    public $regions;

    public function mount($mainData, $cityName)
    {
        $this->cityName = $cityName;
        if (isset($mainData['address'])) {
            $this->formData['address'] = $mainData['address'];
        } else {
            $this->formData['address'] = [
                'title' => '',
                'address' => '',
                'region_id' => '',
                'latLng' => '',
                'address_id' => null,
            ];
        }
    
$this->useOldAddress = $this->formData['address']['address_id'] ? 1 : 0;        
        $this->loadAddresses();
        // بفرستیم به والد
        $this->dispatchFormData();
    }

    public function radioSelected($value)
    {
        $this->formData['address'] = [
            'address_id' => $value,
            'title' => '',
            'address' => '',
            'region_id' => '',
            'latLng' => '',
        ];

        $this->dispatchFormData();
    }

    public function updatedFormData($value, $keys)
    {
        
        if ($keys == 'address.latLng' && $value) {
            $latlng = substr($value, 1, -1);
            $this->formData['address']['latLng'] = $latlng;
        }
        $this->formData['address']['address_id'] = null;

        $this->dispatchFormData();
    }

    public function changeAddressType($value)
    {
        $this->useOldAddress = (bool)$value;

        if ($this->useOldAddress) {
            // اگر کاربر برگشت به آدرس‌های قبلی، مقدار new پاک شود
            $this->formData['address'] = [
                'title' => '',
                'address' => '',
                'region_id' => '',
                'latLng' => '',
                'address_id' => null
            ];
        }

        $this->dispatchFormData();
    }

    public function dispatchFormData()
    {
        // Log::debug('formDataUpdated*******************', [$this->formData]);
        $this->dispatch('formDataUpdated', [
            'key' => 'address',
            'data' => $this->formData['address']
        ]);
    }

    /**
     * Save new address to user_addresses table
     */
    public function saveNewAddress()
    {
        if (!Auth::user()) {
            return;
        }

        $addressData = $this->formData['address'];
        
        if (empty($addressData['title']) || empty($addressData['address'])) {
            return;
        }

        // استخراج latitude و longitude از latLng
        $latitude = null;
        $longitude = null;
        if (!empty($addressData['latLng'])) {
            $coords = explode(',', $addressData['latLng']);
            if (count($coords) == 2) {
                $latitude = trim($coords[0]);
                $longitude = trim($coords[1]);
            }
        }

        $newAddress = UserAddress::create([
            'user_id' => Auth::user()->id,
            'title' => $addressData['title'],
            'address' => $addressData['address'],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'region' => $addressData['region_id'] ?? null,
        ]);

        // آدرس جدید را انتخاب کن
        $this->formData['address']['address_id'] = $newAddress->id;
        $this->useOldAddress = 1;
        
        // لیست آدرس‌ها را دوباره لود کن
        $this->loadAddresses();
        
        $this->dispatchFormData();
    }

    public function loadAddresses()
    {
        if (!Auth::user()) {
            return redirect()->route('web.login');
        }

        $user = User::findOrFail(Auth::user()->id);
        $this->oldAddresses = $user->addresses; // از جدول user_addresses
        
        // فعلاً regions را خالی می‌گذاریم تا بعداً تکمیل شود
        $this->regions = collect([]);
    }

    public function render()
    {
        if (!$this->regions) {
            $this->loadAddresses();
        }
        return view('livewire.address-field');
    }
}
