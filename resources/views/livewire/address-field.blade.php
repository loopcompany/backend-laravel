<div>
    {{-- <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyADPqrZxArohzY5_rjf6XzkEry-uJqP2xU&callback=initMap&language=fa&region=IR&libraries=places">
    </script> --}}
    <div>
        {{-- انتخاب بین آدرس قدیمی یا جدید --}}
        <div class="mb-3">
            <input type="radio" id="old1" wire:model="useOldAddress" wire:change="changeAddressType(1)"
                value="1">
            <label for="old1">آدرس‌های قبلی</label>

            <input type="radio" id="old0" wire:model="useOldAddress" wire:change="changeAddressType(0)"
                value="0" class="ms-4">
            <label for="old0">آدرس جدید</label>
        </div>

        {{-- آدرس‌های قبلی --}}
        @if ($useOldAddress == 1)
            <div class="mb-3">
                @if ($oldAddresses && $oldAddresses->count() > 0)
                    <ul class="list-inline">
                        @foreach ($oldAddresses as $address)
                            <li class="list-inline-item">
                                <input type="radio" id="addr-{{ $address->id }}" class="radio-btn" name="addId"
                                   @if (($formData['address']['address_id'] ?? null) == $address->id) checked @endif
                                    wire:change="radioSelected('{{ $address->id }}')" value="{{ $address->id }}">
                                <label for="addr-{{ $address->id }}" class="btn btn-outline-primary radio-label">
                                    <span>{{ $address->title }}</span><br>
                                    <small>{{ \Illuminate\Support\Str::limit($address->address, 50) }}</small>

                                </label>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="alert alert-warning">آدرسی ذخیره نشده است.</p>
                @endif
            </div>
        @endif

        {{-- فرم آدرس جدید --}}
        @if ($useOldAddress == 0)
            <div class="mb-3">
                <div class="mb-2">
                    <label>محل خدمت را ثبت نمائید.</label>
                    <input type="text" wire:model.live="formData.address.title" class="form-control">
                </div>

                <div class="mb-2">
                    <label>آدرس کامل خود را وارد کنید. </label>
                    <textarea wire:model.live="formData.address.address" class="form-control"></textarea>
                </div>

                <label for="region_id" class="block mb-1 text-sm font-medium text-gray-700">انتخاب منطقه</label>

                <select wire:model.live="formData.address.region_id" id="region_id"
                    class="form-select w-full border-gray-300 rounded">
                    <option value="">-- لطفاً منطقه را انتخاب کنید --</option>
                    @if($regions && $regions->count() > 0)
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->title }}</option>
                        @endforeach
                    @endif
                </select>

                <div class="mb-2">
                    <label>
                        با ثبت موقعیت میتوانید در زمان ثبت سفارش موقعیت تکنسین را که به سمت شما حرکت کرده است را مشاهده
                        نمائید *.
                    </label>

                    <div class="row d-flex justify-content-center">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#set_off_at"
                            class="btn btn-primary-soft w-auto ">
                            انتخاب موقعیت
                        </a>
                    </div>

                    <input type="hidden" id="latLng" wire:model.live="formData.address.latLng" />

                </div>

                {{-- دکمه ذخیره آدرس --}}
                <div class="mb-3">
                    <button wire:click="saveNewAddress" class="btn btn-success btn-sm"
                        @if(empty($formData['address']['title']) || empty($formData['address']['address'])) disabled @endif>
                        ذخیره آدرس
                    </button>
                    <small class="text-muted d-block mt-1">آدرس شما برای استفاده‌های بعدی ذخیره می‌شود</small>
                </div>


            </div>


        @endif
    </div>


    <style>
        .contanerDiv {
            border-radius: 12px;
            background: #9a9ea424;
            height: fit-content;
        }

        .btn-outline-primary.active {
            background-color: #991D9C;
            color: white;
            border-color: #991D9C;
        }
    </style>

</div>
