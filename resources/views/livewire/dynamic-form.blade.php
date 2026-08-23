<div>
    
    <x-fields.stepper :step="$currentStep" :hasMoreSteps="$hasMoreSteps" />
    
    
    
    <form class="row g-3 formContainer" wire:submit.prevent="submit" enctype="multipart/form-data" dir="rtl">
        <div>
    

            @php
                $step = 2;
                if ($currentStep < 3) {
                    $step = 1;
                }
                if ($currentStep == $allSteps->count()) {
                    $step = 3;
                }
            @endphp

            @if ($fields2)
                <h4 class="text-lg font-bold mb-3 pb-3 border-bottom ">مرحله {{ $currentStep }}</h4>
                @foreach ($fields2 as $field)
                    <div class="form-group">

                        @switch($field->type)
                            @case('image')
                                <x-fields.image :field="$field" />
                            @break

                            @case('description')
                                <x-fields.des :field="$field" />
                            @break

                            @case('input')
                                <livewire:input-field :mainData="$formData" :field="$field" :wire:key="'input-' . $field->id" />
                            @break

                            @case('checkbox')
                                <livewire:checkbox-field :mainData="$formData" :field="$field" :wire:key="'checkbox-' . $field->id" />
                            @break

                            @case('radioButton')
                                <livewire:radio-field :mainData="$formData" :field="$field" :wire:key="'radio-' . $field->id" />
                            @break

                            @case('counter')
                                <livewire:counter-field :mainData="$formData" :field="$field" :wire:key="'counter-' . $field->id" />
                            @break

                            @case('file')
                                <livewire:file-field :mainData="$formData" :field="$field" :wire:key="'file-' . $field->id" />
                            @break

                            @case('address')
                                <livewire:address-field :cityName="$cityName" :mainData="$formData" :field="$field"
                                    :wire:key="'address-' . $field->id" />
                            @break

                            @case('preview')
                                <livewire:preview-field :formData="$formData" :categoryId="$categoryId" :wire:key="'preview-' . $field->id" />
                            @break

                            @case('date')
                                <livewire:date-field :field="$field" :wire:key="'date-' . $field->id" />
                            @break

                            @case('time')
                                <livewire:time-field :field="$field" :wire:key="'time-' . $field->id" :categoryId="$categoryId" />
                            @break

                            @case('note')
                                <livewire:note-field :field="$field" :wire:key="'note-' . $field->id" />
                            @break

                            @default
                                <p> فیلد تعریف تشده

                                    {{ $field->type }}
                                </p>
                        @endswitch
                    </div>
                @endforeach
                <div class="flex justify-between pt-5 mt-6">
                    @if ($currentStep > 1)
                        <button wire:click="prevStep" type="button" class="btn btn-primary">مرحله
                            قبل</button>
                    @endif

                    @if ($hasMoreSteps)
                        <button wire:click="nextStep" wire:loading.attr="disabled" type="button"
                            class="btn btn-primary">
                            <span wire:loading.remove>مرحله بعد</span>
                            <span wire:loading>
                                در حال پردازش...
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    @else
                        <button type="submit" wire:loading.attr="disabled" class="btn btn-success">
                            <span wire:loading.remove>ثبت نهایی</span>
                            <span wire:loading>
                                در حال ذخیره...
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    @endif

                </div>
            @else
                <p class="mt-3">در حال حاضر این خدمت جهت ثبت سفارش فعال نمی‌باشد.</p>
            @endif


        </div>
    </form>
    

    <style>
        .formContainer label,
        .formContainer h6,
        .formContainer small {
            font-family: 'vazir-bold', sans-serif !important;
            margin-top: 10px;
        }
    </style>

@if(!$is_validTime && $both_times)
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger">
              <strong class="me-auto text-white">خطا در ثبت سفارش</strong>
              <small class="text-white">لوپ</small>
              <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
              زمان نامعتبر است
            </div>
        </div>
    </div>
@endif
@if($errorMessage2)
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger">
              <strong class="me-auto text-white">خطا در ثبت سفارش</strong>
              <small class="text-white">لوپ</small>
              <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
              لطفا فیلد های اجباری را تکمیل نمائید.
            </div>
        </div>
    </div>
@endif
    
</div>
