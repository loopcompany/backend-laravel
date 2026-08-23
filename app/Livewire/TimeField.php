<?php

namespace App\Livewire;

use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log as FacadesLog;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class TimeField extends Component
{
    public $timeSlots = [];
    public $isUrgent;
    public $isToday = false;
    public $selectedDate = null;
    public $formData = [];
    public $field;
    public $categoryId;
    public $categoryStart;
    public $categoryEnd; 
    public $categoryDuration;
    protected $listeners = ['dateChanged' => 'handleDateChange'];

    public function mount($field, $categoryId, $isUrgent = false)
    {
        $this->field = $field;
        $this->isUrgent = $isUrgent;
        $this->categoryId = $categoryId;

        try {
            $category = Category::findOrFail($categoryId);
            
            // ذخیره اطلاعات category برای استفاده بعدی
            $this->categoryStart = $category->start_at;
            $this->categoryEnd = $category->end_at;
            $this->categoryDuration = $category->duration;
            
            // Log debug information
            FacadesLog::info('TimeField Debug:', [
                'category_id' => $categoryId,
                'category_title' => $category->title,
                'start_at' => $category->start_at,
                'end_at' => $category->end_at,
                'duration' => $category->duration
            ]);
            
            $this->generateTimeSlots();
        } catch (\Exception $e) {
            // اگر category پیدا نشد، از مقادیر پیش‌فرض استفاده کن
            FacadesLog::error('Category not found: ' . $e->getMessage());
            $this->categoryStart = '08:00';
            $this->categoryEnd = '20:00';
            $this->categoryDuration = 60;
            $this->generateTimeSlots();
        }

        $this->formData['time'][] = ['value' => ''];

        $this->dispatch('formDataUpdated', [
            'key' => 'time',
            'data' => $this->formData['time']
        ]);
    }

    public function radioSelected($value)
    {
        $this->formData['time'][0]['value'] = $value;


        $this->dispatch('formDataUpdated', [
            'key' => 'time',
            'data' => $this->formData['time']
        ]);
        
    //     $this->selectedDate = $value;

    // $today = Jalalian::now()->format('Y/m/d');
    // $this->isToday = $value === $today;
    }
    
    

    public function handleDateChange($payload)
    {
        $this->isToday = $payload['isToday'] ?? false;
        $this->selectedDate = $payload['selectedDate'] ?? null;
        
        // اگر تاریخ تغییر کرد، ساعت انتخابی را پاک کن
        $this->formData['time'][0]['value'] = '';
        
        // timeSlots را دوباره تولید کن
        $this->generateTimeSlots();
        
        $this->dispatch('formDataUpdated', [
            'key' => 'time',
            'data' => $this->formData['time']
        ]);
    }

    public function generateTimeSlots()
    {
        // بررسی و تصحیح مقادیر
        $start = $this->categoryStart;
        $end = $this->categoryEnd;
        $duration = $this->categoryDuration;
        
        if (empty($start) || !is_string($start) || !preg_match('/^\d{2}:\d{2}$/', $start)) {
            $start = '08:00';
        }
        if (empty($end) || !is_string($end) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
            $end = '20:00';
        }
        if (empty($duration) || !is_numeric($duration) || $duration <= 0) {
            $duration = 60;
        }

        FacadesLog::info('Generating time slots:', [
            'start' => $start,
            'end' => $end,
            'duration' => $duration,
            'start_type' => gettype($start),
            'end_type' => gettype($end),
            'duration_type' => gettype($duration)
        ]);

        try {
            $now = Carbon::now();
            $startTime = Carbon::createFromTimeString($start);
            $endTime = Carbon::createFromTimeString($end);

            $slots = [];
            $counter = 0;
            while ($startTime < $endTime && $counter < 50) { // محدودیت برای جلوگیری از حلقه بی‌نهایت
                $slot = $startTime->format('H:i');
                
                // اگر امروز است، ساعت‌های گذشته را نادیده بگیر
                if ($this->isToday) {
                    $slotTime = Carbon::createFromTimeString($slot);
                    $currentTimePlus2Hours = $now->copy()->addHours(2);
                    
                    // اضافه کردن 2 ساعت به زمان فعلی تا کاربر وقت آماده شدن داشته باشد
                    if ($slotTime->lessThanOrEqualTo($currentTimePlus2Hours)) {
                        $startTime->addMinutes((int)$duration);
                        $counter++;
                        continue;
                    }
                }
                
                $slots[] = $slot;
                $startTime->addMinutes((int)$duration);
                $counter++;
            }

            $this->timeSlots = $slots;
            
            FacadesLog::info('Generated time slots:', [
                'slots_count' => count($slots),
                'slots' => $slots,
                'is_today' => $this->isToday,
                'current_time' => $now->format('H:i')
            ]);
            
        } catch (\Exception $e) {
            // در صورت خطا، مقادیر پیش‌فرض
            $this->timeSlots = ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];
            FacadesLog::error('Error generating time slots: ' . $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.time-field');
    }
}
