<?php

namespace App\Livewire;

use App\Models\Gallery;
use App\Models\OrderDetail;
use App\Models\TechnicianCategory;
use App\Models\UserAddress;
use App\Models\ShowOrderRegion;
use App\Models\TechnicianNeighbourhood;
use Livewire\Component;
use App\Models\CategoryField;
use App\Models\Field;
use App\Models\FieldDetail;
use App\Models\DiscountCode;
use App\Models\Technician;
use App\Models\DiscountUse;
use App\Models\CategoryFieldConditional;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Livewire\Livewire;
use App\Helpers\Helper;
use Carbon\Carbon;
use App\Models\Category;
use Illuminate\Support\Arr;

class DynamicForm extends Component
{
    public $categoryId;
    public $cityName;
    public $currentStep = 1;
    public $formData = [];
    public $allSteps = [];
    public $newConditional = [];
    public $order_id = null;
    public $pendingSteps;
    public $both_times=false;
    public $is_validTime=true;
    public $hasMoreSteps = true;
    public $is_edit = false;
    public $errorMessage2 = '';
    public $errorMessage = '';
    protected $listeners = [
        'radioSelected' => 'handleRadioSelection',
        'mapLocationSelected' => 'updateLocation',
        'formDataUpdated' => 'updateFormDataFromChild',
    ];

    public $location = [
        'lat' => null,
        'lng' => null
    ];
    public function updateLocation($data)
    {
        $this->location = $data;
        $this->formData['address']['latLng'] = $data['lat'] . ',' . $data['lng'];
        $this->dispatch('locationUpdated'); // اطلاع به ویو برای آپدیت
        // Log::debug('موقعیت دریافت شد:', $data);
    }

    public function updateFormDataFromChild($data)
    {
        $this->formData[$data['key']] = $data['data'];
        
        if (isset($this->formData['time'][0]['value'], $this->formData['date'][0]['value'])) {
            // Log::debug('*************************');
            $timeValue = $this->formData['time'][0]['value'];
            $dateValue = $this->formData['date'][0]['value'];
        
            if(!$timeValue || !$dateValue){
                $this->is_validTime = true;
            }else{
                $this->both_times = true;
            }
            // بررسی صحت فرمت زمان
            if (preg_match('/^\d{1,2}:\d{1,2}$/', $timeValue)) {
                try {
                    [$hour, $minute] = explode(':', $timeValue);
        
                    $inputDateTime = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $dateValue)
                        ->toCarbon()
                        ->setTime((int)$hour, (int)$minute);
        
                    $nowPlus4 = now()->addHours(4);
        
                    $this->is_validTime = $inputDateTime->greaterThanOrEqualTo($nowPlus4);
                    
                    if(!$this->is_validTime){
                        $this->formData['time'][0]['value'] = '';
                    }
        
                    
        
                } catch (\Exception $e) {
                    $this->is_validTime = false;
                    $this->formData['time'][0]['value'] = '';
                    
                    Log::error('Date/time parse error: ' . $e->getMessage());
                }
            } else {
                $this->is_validTime = false;
                $this->formData['time'][0]['value'] = '';
                Log::warning('Invalid time format: ' . $timeValue);
            }
        }

        
        // Log::debug('فرم‌دیتا آپدیت شد از فرزند:', [$this->formData]);
    }

    public function mount($categoryId, $cityName, $formData = [], $order_id = null)
    {

        if (count($formData) > 0) {
            $this->is_edit = true;
            $this->order_id = $order_id;
        }
        $this->formData = $formData;
        $this->cityName = $cityName;
        $this->allSteps = collect();
        $this->categoryId = $categoryId;
        $this->loadAllSteps();
        $this->errorMessage2 = '';
    }

    protected function loadAllSteps()
    {
        $steps = CategoryField::with(['field.field_details'])
            ->where('category_id', $this->categoryId)
            ->orderBy('step', 'asc')
            ->orderBy('sort', 'asc')
            ->get()
            ->groupBy('step');

        $this->allSteps = $steps->map(function ($items) {
            return $items->pluck('field')->unique('id');
        });

        $dateTimeStep = collect([
            (object)[
                'id' => 'date',
                'title' => 'تاریخ مناسب برای اجرای درخواست',
                'type' => 'date',
                'is_required' => 1,
                'icon_name' => 'calendar',
                'des' => '',
                'value' => null,
                'field_details' => null,

            ],
            (object)[
                'id' => 'time',
                'title' => 'ساعت مناسب برای اجرای درخواست',
                'type' => 'time',
                'is_required' => 1,
                'icon_name' => 'time',
                'des' => '',
                'value' => null,
                'field_details' => null,

            ],
        ]);

        $addressStep = collect([
            (object)[
                'id' => 'address',
                'title' => 'آدرس برای اجرای درخواست',
                'type' => 'address',
                'is_required' => 1,
                'icon_name' => 'map',
                'des' => '',
                'value' => null,
                'field_details' => null,

            ],
        ]);

    
    $category = Category::find($this->categoryId);
    if($category->has_gender == '1'){
        $genderStep = collect([
            (object)[
                'id' => 'gender',
                'title' => 'جنسیت تکنسین مورد نظر',
                'type' => 'counter',
                'is_required' => 1,
                'icon_name' => 'calendar',
                'des' => '',
                'value' => null,
                'field_details' => null,

            ],
        ]);
    }
        $descriptionStep = collect([

            (object)[
                'id' => 'note',
                'title' => 'توضیحات نهایی',
                'type' => 'input',
                'is_required' => 0,
                'icon_name' => 'document-text',
                'des' => 'مثلا نحوه‌ی هماهنگی یا جزئيات بیشتر ...',
                'value' => null,
                'field_details' => [
                    (object) [
                        'id' => -2,
                        'is_required' => 0,
                        'title' => 'توضیحات نهایی',
                        'long_des' => true,
                    ]
                ],
            ],
            (object)[
                'id' => 'file',
                'title' => 'بارگذاری تصویر',
                'type' => 'file',
                'is_required' => 0,
                'icon_name' => 'attach',
                'des' => 'در صورت نیاز می‌توانید با بارگذاری تصویر در این قسمت به تشخیص بهتر تکنسین‌ها در رابطه با خدمت مورد نظر خود کمک کنید.',
                'value' => null,
            ],
        ]);

        $preview = collect([
            (object)[
                'id' => 'preview',
                'title' => 'توضیحات نهایی',
                'type' => 'preview',
                'is_required' => 0,
                'icon_name' => 'document-text',
                'des' => '',
                'value' => null,
            ],

        ]);

        $this->allSteps->splice(1, 0, [$dateTimeStep]);
        $this->allSteps->splice(2, 0, [$addressStep]);
        if($category->has_gender == '1'){
            $this->allSteps->push($genderStep);
        }
        $this->allSteps->push($descriptionStep);
        $this->allSteps->push($preview);
        $this->allSteps = $this->allSteps->values()
            ->keyBy(fn($_, $index) => $index + 1);



        $this->checkHasMoreSteps();
    }

    public function handleRadioSelection($fieldId, $value)
    {
        $this->pendingSteps = $this->allSteps;

        $categoryField = CategoryField::where('field_id', $fieldId)
            ->where('category_id', $this->categoryId)
            ->where('is_conditional', '1')
            ->first();

        if (!$categoryField) {
            return;
        }

        $startIndex = (int)$this->currentStep + 1;

        $keyForThisCondition = (string)$categoryField->id;
        if (isset($this->newConditional[$keyForThisCondition])) {
            $this->removePreviousCondition($keyForThisCondition);
        }

        $conditionalSteps = CategoryFieldConditional::with(['field.field_details'])
            ->where('field_detail_id',  $value)
            ->where('category_field_id', $categoryField->id)
            ->orderBy('step', 'asc')
            ->get()
            ->groupBy('step');

        if ($conditionalSteps->count() < 1) {
            if ($this->pendingSteps !== null) {
                $this->allSteps = $this->pendingSteps
                    ->values()
                    ->keyBy(fn($_, $i) => $i + 1);
                $this->pendingSteps = null;
                $this->checkHasMoreSteps();
            }
            unset($this->newConditional[$keyForThisCondition]);
            return;
        }

        $newStep = [];
        foreach ($conditionalSteps as $_step => $fields) {
            $newStep[] = $fields->pluck('field')->unique('id');
        }

        $firstPart = $this->pendingSteps->slice(0, max(0, $startIndex - 1))->values();
        $lastPart  = $this->pendingSteps->slice(max(0, $startIndex - 1))->values();

        $this->pendingSteps = $firstPart
            ->merge($newStep)
            ->merge($lastPart)
            ->values()
            ->keyBy(fn($_, $index) => $index + 1);

        $this->newConditional[$keyForThisCondition] = [
            'categoryField'  => $categoryField->id,
            'fieldDdetailID' => $value,
            'startIndex'     => $startIndex,
            'length'         => count($newStep),
        ];

        $this->allSteps = $this->pendingSteps;
        $this->pendingSteps = null;
        $this->checkHasMoreSteps();
    }



    public function removePreviousCondition($key)
    {
        if (!isset($this->newConditional[$key])) {
            return false;
        }

        $meta = $this->newConditional[$key];

        $startIndex = (int)($meta['startIndex'] ?? 0);
        $length     = (int)($meta['length'] ?? 0);

        if ($startIndex <= 0 || $length <= 0) {
            unset($this->newConditional[$key]);
            return false;
        }

        $working = $this->pendingSteps ?: $this->allSteps;

        $indexesToRemove = range($startIndex, $startIndex + $length - 1);

        $working = $working
            ->reject(fn($_item, $k) => in_array((int)$k, $indexesToRemove, true))
            ->values()
            ->keyBy(fn($_, $i) => $i + 1);

        $this->pendingSteps = $working;

        unset($this->newConditional[$key]);

        return true;
    }



    public function checkHasMoreSteps()
    {

        if ($this->currentStep < $this->allSteps->count()) {
            $this->hasMoreSteps = true;
        } else {
            $this->hasMoreSteps = false;
        }


        // Log::debug('hasMoreSteps: ', ['hasMoreSteps' => $this->hasMoreSteps, 'currentStep' => $this->currentStep, 'allSteps' => $this->allSteps->count()]);
    }


        public function getAllRequiredFieldsFilledProperty()
    {
        $fields = $this->allSteps[$this->currentStep] ?? collect();

        foreach ($fields as $field) {
            if (!is_object($field)) {
                continue;
            }

            // Address
            if ($field->type === 'address') {
                $address = $this->formData['address'] ?? [];
                $addressId = Arr::get($address, 'address_id');
                if (empty($addressId)) {
                    if (
                        empty(Arr::get($address, 'title')) ||
                        empty(Arr::get($address, 'region_id')) ||
                        empty(Arr::get($address, 'latLng')) ||
                        empty(Arr::get($address, 'address'))
                    ) {
                        return false;
                    }
                }
                continue;
            }

            // File
            if ($field->type === 'file') {
                if ((int)$field->is_required === 1) {
                    $files = $this->formData['file'] ?? [];
                    $hasFile = false;
                    foreach ($files as $f) {
                        if (!empty(Arr::get($f, 'filename'))) {
                            $hasFile = true;
                            break;
                        }
                    }
                    if (!$hasFile) {
                        return false;
                    }
                }
                continue;
            }

            // Input
            if ($field->type === 'input') {
                $bucket = $this->formData[$field->id] ?? [];
                if ($field->id == '0' || $field->id == '-1') {
                    $first = $bucket[0] ?? null;
                    if ($first === null || !trim((string)Arr::get($first, 'value'))) {
                        return false;
                    }
                } else {
                    if (!empty($field->field_details)) {
                        foreach ($field->field_details as $detail) {
                            if ((int)$detail->is_required === 1) {
                                $foundValid = false;
                                foreach ($bucket as $data) {
                                    if ((int)Arr::get($data, 'detail_id') === (int)$detail->id) {
                                        if (trim((string)Arr::get($data, 'value')) !== '') {
                                            $foundValid = true;
                                            break;
                                        }
                                    }
                                }
                                if (!$foundValid) {
                                    return false;
                                }
                            }
                        }
                    }
                }
                continue;
            }

            // Radio / Checkbox / Counter
            if (in_array($field->type, ['radioButton', 'checkbox', 'counter'], true)) {
                if ((int)$field->is_required === 1) {
                    $values = $this->formData[$field->id] ?? [];
                    $ok = false;
                    foreach ($values as $data) {
                        $val = Arr::get($data, 'value');
                        // هم مقدار بولین/عددی/رشته‌ای را معتبر حساب می‌کنیم
                        if ($val !== null && $val !== '' && $val !== 0 && $val !== '0') {
                            $ok = true;
                            break;
                        }
                    }
                    if (!$ok) {
                        return false;
                    }
                }
                continue;
            }
        }

        return true;
    }




    public function nextStep()
    {
        // Log::debug('DynamicForm nextStep*************** ', [$this->formData]);


        if ($this->pendingSteps !== null) {
            $this->allSteps = $this->pendingSteps;
            $this->pendingSteps = null;
        }

        if (!$this->allRequiredFieldsFilled) {
            $this->errorMessage2 = 'لطفاً فیلدهای اجباری را پر کنید.';
            return;
        }
        $this->errorMessage2 = '';

        if ($this->hasMoreSteps) {
            $this->currentStep++;
        }
        $this->checkHasMoreSteps();
    }

    public function prevStep()
    {

        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function render()
    {
        $this->checkHasMoreSteps();
        if (!$this->hasMoreSteps) {
            $this->hasMoreSteps = false;
        }
        $fields = $this->allSteps[$this->currentStep] ?? [];
        // Log::debug("checkHasMoreSteps: " . $this->hasMoreSteps);
        return view('livewire.dynamic-form', [
            'fields2' => $fields,
            'formData' => $this->formData,
        ]);
    }




    public function submit()
    {
        if (!Auth::check()){
            return redirect()->route('web.login')->with('error', 'لطفا ابتدا وارد شوید و مجددا تلاش نمائید.');
        }
        // Log::debug('Submit method called', ['formData' => $this->formData]);
        // dd('****');
        $pakar_price = 0;

        $code = $this->formData['preview']['value']?? null;

        if (!$this->allRequiredFieldsFilled) {
            $this->errorMessage = 'لطفاً فیلدهای اجباری را پر کنید.';
            return;
        }
        $this->errorMessage = '';

        
        if ($this->formData['address']['address_id'] ?? false) {
            $address_id = $this->formData['address']['address_id'];
        } else {

            if ($this->formData['address']['latLng']?? false) {
                $latlng = explode(',', $this->formData['address']['latLng']);
                $lat = $latlng[0];
                $lng = $latlng[1];
            }

            $userAdress = new UserAddress;
            $userAdress->title = $this->formData['address']['title']?? null;
            $userAdress->user_id = Auth::user()->id;
            $userAdress->neighbourhood_id = $this->formData['address']['region_id']?? null;
            $userAdress->latitude = $lat ?? '';
            $userAdress->longitude = $lng ?? '';
            $userAdress->address = $this->formData['address']['address']?? null;
            try {
                $userAdress->save();
            } catch (\Exception $e) {
                Log::error('خطای ذخیره آدرس: ' . $e->getMessage());
                return;
            }

            $address_id = $userAdress->id;
        }

        $images = $this->formData['file'] ?? [];

        $order = new Order;
        $order->user_id = Auth::user()->id;
        $order->category_id = $this->categoryId;
        $order->status = 0;
        $order->pakar_price = null;
        $order->discount_price = null;

        $order->user_address_id = $address_id;
        $order->des = $this->formData['note'][0]['value']?? null;
       try {
            $order->date = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $this->formData['date'][0]['value']))->toCarbon();
        } catch (\Exception $e) {
            Log::error('تاریخ نامعتبر: '.$e->getMessage());
            $order->date = null;
        }
        $order->time = $this->formData['time'][0]['value'] ?? null;
        $filename = $images[0]['filename'] ?? null;
        $order->image_path = $filename ? 'order/' . $filename : null;
        
        $genderData = $this->formData['gender'] ?? [];
        $order->female_count = collect($genderData)->firstWhere('detail_id', 'female')['value'] ?? 0;
        $order->male_count = collect($genderData)->firstWhere('detail_id', 'male')['value'] ?? 0;
        $order->unspecified_count = collect($genderData)->firstWhere('detail_id', 'any')['value'] ?? 0;
        
        if($order->female_count==0 && $order->male_count==0 && $order->unspecified_count==0){
            $order->unspecified_count = 1;
        }
        
        $category = Category::find($this->categoryId);
        if($category->is_fixed == 1 && $order->pakar_price){
            $order->is_fixed=1;
        }
        
        
        try {
            $order->save();
        } catch (\Exception $e) {
            Log::error('خطای ذخیره آدرس: ' . $e->getMessage());
            return;
        }


        $pakaPrice = 0;


        foreach ($this->formData as $fieldId => $data) {
            if (!in_array($fieldId, ['date', 'time', 'address', 'note', 'file', 'preview', 'gender'])) {
                foreach ($data as $detail) {
                    // Log::debug('*/*/*/*/*/*/*/*/*/', [$fieldId, $detail]);
                    if ($detail['value'] != 0) {
                        $order_detail = new OrderDetail;
                        $order_detail->order_id = $order->id;
                        $order_detail->field_id = $fieldId;
                        $order_detail->field_detail_id = $detail['detail_id'];
                        $order_detail->value = $detail['value'];
                        $order_detail->price = $detail['price'];
                        $order_detail->save();

                        $price = array_reduce((array) $data, function ($carry, $item) {
                            if (is_array($item) && array_key_exists('value', $item)) {
                                return $carry + (float)($item['price'] ?? 0) * (int)$item['value'];
                            }
                            return $carry;
                        }, 0);
        
                    }
                }
                        $pakaPrice += (float)$price;
            }
        }

        $order->pakar_price = (int)$pakaPrice;
        if ($order->save()) {
            
            $result = Helper::discount_check($code, $this->categoryId);
            $responseArr = $result->getData(true);
            
            if (isset($responseArr['discount_code_id'])) {
                $discountCode = DiscountCode::find($responseArr['discount_code_id']);
            
                if ($discountCode && $code) {
                    DiscountUse::create([
                        'order_id'         => $order->id,
                        'user_id'          => $discountCode->user_id,
                        'discount_code_id' => $discountCode->id,
                    ]);
            
                    $discountCode->decrement('count'); 
                }
                $max_price = (int) $discountCode->club->max_price;
                $discount_price = (int)$pakaPrice*$discountCode->discount_percent/100;
                $discount_price2 = $discount_price > (int)$max_price ? (int)$max_price : $discount_price;
                $order->discount_price = $discount_price2;
                $order->save();
            }

            Helper::send_sms(Auth::user()->phone, '957924', ['NAME','ID'], [Auth::user()->name.' '.Auth::user()->last_name,$order->id]);
            

            $female_count = (int)$order->female_count ;
            $male_count = (int)$order->male_count ;
            $unspecified_count = (int)$order->unspecified_count ;

            if($female_count+$male_count+$unspecified_count > 1){
                $is_legal = true ;
            }else{
                $is_legal = false ;
            }

            $gender='any';
            if($male_count==1){ $gender='0'; }
            elseif($female_count==1){ $gender='1'; }
            $showOrder = ShowOrderRegion::latest()->first();

             $categoryTechIds = TechnicianCategory::where('category_id', $order->category_id)
                ->pluck('technician_id')
                ->toArray();


            if ($showOrder->show_all==0) {
                $userAddress = UserAddress::find($address_id);
            
                if ($userAddress) {
                    $neighbourhoodId = $userAddress->neighbourhood_id;

                    $neighbourhoodTechIds = TechnicianNeighbourhood::where('neighbourhood_id', $neighbourhoodId)
                        ->pluck('technician_id')
                        ->toArray();

                    $commonTechIds = array_intersect($neighbourhoodTechIds, $categoryTechIds);

                    $technicians = Technician::whereIn('id', $commonTechIds);
                    if($is_legal){
                        $technicians = $technicians->where('is_legal', 1 );
                    }
                    $technicians = $technicians->get();

                    foreach ($technicians as $tech) {
                        if ($tech && $tech->status==1 && $tech->active==1 && $tech->has_access==1 && ($gender=='any' || $gender==$tech->gender)) {
                            Helper::send_sms($tech->phone, '201055', ['ID'], [$order->id]);
                        }
                    }
                }
            } else {
                $technicians = Technician::whereIn('id', $categoryTechIds);
                if($is_legal){
                    $technicians = $technicians->where('is_legal', 1 );
                }
                $technicians = $technicians->get();

                foreach ($technicians as $tech) {
                    if ($tech && $tech->status==1 && $tech->active==1 && $tech->has_access==1 && ($gender=='any' || $gender==$tech->gender)) {
                        Helper::send_sms($tech->phone, '201055', ['ID'], [$order->id]);
                    }
                }
            }
        
        
            return redirect()->route('user.orders')->with('success', 'سفارش شما با موفقیت ثبت شد و در انتظار پیشنهادات تکنسین‌ها می‌باشد.');
        }
    }

}
