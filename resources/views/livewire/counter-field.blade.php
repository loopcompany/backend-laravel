<div>

    <h6 class="input-title p-3 mt-5">{{ $field->title }} {{ $field->is_required ? '*' : '' }}</h6>
    <div class="row">
    @if ($field->id === 'gender')
        @php
            $genderOptions = [
                ['id' => 'female', 'title' => 'تکنسین خانم'],
                ['id' => 'male', 'title' => 'تکنسین آقا'],
                ['id' => 'any', 'title' => 'فرقی نمی‌کند'],
            ];
        @endphp
    
        @foreach ($genderOptions as $detail)
            <div class="col-lg-3 col-md-4 col-6">
                <label class="mt-2">{{ $detail['title'] }}</label>
                <div style="display: flex; flex-direction: row; align-items: center;">
                    <button wire:click="increment('{{ $field->id }}', '{{ $detail['id'] }}')" class="btn btn-primary button" type="button">+</button>
                    <input class="inputs" value="{{ $count[$detail['id']] }}" type="text" readonly />
                    <button wire:click="decrement('{{ $field->id }}', '{{ $detail['id'] }}')" class="btn btn-primary button button2" type="button">-</button>
                </div>
            </div>
        @endforeach
    @else
        @foreach ($field->field_details as $detail)
            <div class="col-lg-3 col-md-4 col-6">
                <label class="mt-2">{{ $detail->title }}</label>
                @if ($detail->price && $detail->show_price)
                    <span style="color:#FFA801">(
                        {{ number_format($count[$detail->id] > 0 ? $detail->price * $count[$detail->id] : (int)$detail->price) }}
                        تومان )</span>
                @endif
    
                <div style="display: flex; flex-direction: row; align-items: center;">
                    <button wire:click="increment({{ $field->id }}, {{ $detail->id }})" class="btn btn-primary button" type="button">+</button>
                    <input class="inputs" value="{{ $count[$detail->id] }}" type="text" readonly />
                    <button wire:click="decrement({{ $field->id }}, {{ $detail->id }})" class="btn btn-primary button button2" type="button">-</button>
                </div>
            </div>
        @endforeach
    @endif

    </div>




    <style>
        .button {
            margin: 0 0 0 9px;
            padding: 5px;
            height: 31px;
            width: 31px;
            border-radius: 112%;
        }

        .button2 {
            margin: 0 9px 0 0 !important;
        }

        .inputs {
            border: none;
            background: #e8e8e8;
            border-radius: 15px;
            height: 31px;
            text-align: center;
            width: 50px;
        }
    </style>
</div>
