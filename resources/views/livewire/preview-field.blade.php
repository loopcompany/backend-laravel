<div>
    <div class="row">
        <h5 class="mb-3">وضعیت: در انتظار ثبت نهایی</h5>
        <div class="col-lg-6">
            <p>
                <span> </span>
                {{ $category->title }}
            </p>
            <p>
                <span>تاریخ مراجعه تکنسین: </span>
                {{ $date }}
            </p>
            <p>
                <span>ساعت مراجعه تکنسین: </span>
                {{ $time }}
                </p>
            <p>
                <span>آدرس محل خدمت: </span>
                {{ $address }}
            </p>

        </div>
        <div class="col-lg-6">
            <p>
                <span>تکنسین: </span>
                ندارد
            </p>
            <p>
                <span>قیمت پیشنهادی تکنسین: </span>
                ثبت نشده
            </p>
            
            @if(!is_null($pakaPrice) && $category->is_fixed)
            <p>
               <span>مبلغ قطعی لوپ: </span>
                @if($pakaPrice) {{ $pakaPrice }} تومان @else توافقی @endif
            </p>
            @else
            <p>
               <span>مبلغ پایه لوپ: </span>
               @if($pakaPrice) {{ $pakaPrice }} تومان @else توافقی @endif
            </p>
            @endif
        </div>
    </div>




<div class="co-md-12">
    @foreach ($formData as $fieldId => $data)
    
        @if (!in_array($fieldId, ['urgent', 'date', 'time', 'address', 'note', 'file', 'preview', 'gender', 'preview_1']))
            <div class="orderCart mt-3">

                @php
                Illuminate\Support\Facades\Log::debug('$fieldId', [$fieldId]);
                    $fields = App\Models\Field::find($fieldId);
                @endphp
                <p class="orderTitle">
                    <span style="font-weight:bold">{{ $fields->title }} :
                    </span>
                </p>
                @foreach ($data as $detail) 
                    @if($detail['value'] > 0)

                        @php
                            $detail_id = $detail['detail_id'];
                            $fieldDetail = App\Models\FieldDetail::find($detail_id);
                            $type = $fieldDetail->field->type;
                        @endphp

                            @switch($type)
                                @case('input')
                                    <p>
                                        <span>{{ $fieldDetail->second_title }} : </span>
                                        {{ $detail['value'] }}
                                    </p>
                                @break

                                @case('checkbox')
                                    <p>{{ $fieldDetail->title }} @if($fieldDetail->has_counter) ×{{ $detail['value'] }} @endif</p>
                                @break

                                @case('radioButton')
                                    <p>{{ $fieldDetail->title }} @if($fieldDetail->has_counter) ×{{ $detail['value'] }} @endif</p>
                                @break

                                @case('counter')
                                    <p>
                                        <span>{{ $fieldDetail->title }} : </span>
                                        ×{{ $detail['value'] }}
                                    </p>
                                @break

                                @default
                            @endswitch
                    @endif
                @endforeach
            </div>
        @endif

        @if($fieldId == 'gender')
        @php
            $genderData = $formData['gender'] ?? [];
            $female_count = collect($genderData)->firstWhere('detail_id', 'female')['value'] ?? null;
            $male_count = collect($genderData)->firstWhere('detail_id', 'male')['value'] ?? null;
            $unspecified_count = collect($genderData)->firstWhere('detail_id', 'any')['value'] ?? null;
        @endphp
        <div class="orderCart mt-3">
            <p class="orderTitle">
                <span style="font-weight:bold">جنسیت تکنسین</span>
            </p>
            <p>
                <span style="font-weight:bold"> تکنسین خانم : </span>
            {{ $female_count }}
            </p>
            <p>
                <span style="font-weight:bold"> تکنسین آقا : </span>
            {{ $male_count }}
            </p>
            <p>
                <span style="font-weight:bold">فرقی نمیکند : </span>
            {{ $unspecified_count }}
            </p>
            
        </div>
        @if(isset($formData['note']) && isset($formData['note'][0]['value']) && $formData['note'][0]['value'])
            <div class="orderCart mt-3">
                <p class="orderTitle">
                    <span style="font-weight:bold">توضیحات : </span>
                </p>
                {{ $formData['note'][0]['value']?? '' }}
            </div>
        @endif


        @if(isset($formData['file']) && $formData['file'])
         <div class="orderCart mt-3">
            <p class="orderTitle">
                <span>
                    تصویر :
                </span>
            </p>
                <div class="row">
                    @foreach($formData['file'] ?? [] as $img)
                    <div class="col-lg-3">
                        <img src="{{asset('storage/order/'.$img['filename'])}}" style="width:200px; height:200px; object-fit:cover; border-radius:5px" />
                    </div>
                    @endforeach
                </div>
            </div>
        @endif

        @endif
    @endforeach


    

</div>






<label  class="mt-3 mb-1">
   کد تخفیف  
</label>
<p>مشتری گرامی جهت مشاهده کد های تخفیف و جوایز خود به باشگاه مشتریان در بخش کلاب مراجعه نمایید</p>
    <div class="row"
        style="display: flex;justify-content: center;background: #f5ad25;padding-top: 10px;border-radius: 10px;">
        <div class="col-lg-6">
            <div id="myForm" style="display:flex;padding: 5px 0px;margin-bottom:10px">
                <input type="hidden" name="order_id" value="473">
                <div class="col-lg-10">
                    <input class="form-control pe-5 bg-white border-0 h-100 " type="search"
                        style="color:#991D9C; padding-left: 12px !important;" placeholder="کد تخفیف" name="discount"
                        id="discount" aria-label="Search" wire:model.live="formData.preview_1"/>
                    <input type="hidden" id="idd" value="{{ $category->id }}" />
                </div>
                <div class="col-lg-2" style="display: flex;align-items: center;">

                    <button type="button" id="submitBtn" onclick="discountcheck()"
                        class="btn btn-white btnWizard btnWizardID-2 discountBtn h-100">اعمال</button>

                </div>
            </div>
        </div>
    </div>
    <div id="result" style="text-align: center"></div>
    <style>
        .discountBtn {
            padding: 5px;
            width: 100%;
            border: none;
            color: #f5ad25;
            border-right: dashed 2px #f5ad25;
            border-radius: 8px;
            margin-bottom: 1px;

        }
        .orderCart {
            box-shadow: 1px 1px 9px #a1a1a140;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px
        }

        .orderTitle {
            background: #f5e8f5;
            padding: 7px;
            border-radius: 5px;
            color: #9b059d;
        }
    </style>

</div>
