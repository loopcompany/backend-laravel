<div>
    <h6 class="input-title p-3 mt-5">{{ $field->title }} {{ $field->is_required ? '*' : '' }}
    <br>
    <small>{{ $field->des }}</small></h6>
    
    <div class="radio-group row">
        @foreach ($field->field_details as $detail)
            <div class="align-items-center d-flex flex-column contanerDiv col-lg-6 mt-2">
                <input type="radio" class="radio-btn" id="detail_{{ $detail->id }}" value="{{ $detail->id }}"
                    name="radio_group_{{ $field->id }}" @if ($field->is_required) required @endif
                    @checked($detail->is_checked == 1 || $this->is_checked($field->id, $detail->id))
                    wire:change="radioSelected({{ $field->id }}, {{ $detail->id }})">
                    
                <label class="radio-label" for="detail_{{ $detail->id }}">
                    {{ $detail->title }}
                    
                    @if ($this->is_checked($field->id, $detail->id) && !empty($detail->des))
                        <div class="text-muted mt-2 small text-center w-100 px-3">
                            {{ $detail->des }}
                        </div>
                    @endif
                    
                    @if ($detail->price && $detail->show_price)
                        <span style="color:#FFA801; white-space: nowrap;">
                            ({{ number_format($count[$detail->id] > 0 ? $detail->price * $count[$detail->id] : $detail->price) }} تومان)
                        </span>
                    @endif
                </label>

                @if ($detail->has_counter && $this->is_checked($field->id, $detail->id))
                    <div class="counter-controls mt-2 mb-3 d-flex align-items-center gap-2 px-2 ">
                        <button type="button" class="btn btn-orange-soft btn-sm"
                            wire:click="decrement({{ $field->id }}, {{ $detail->id }})">-</button>
                        <span>{{ $count[$detail->id] }}</span>
                        <button type="button" class="btn btn-orange-soft btn-sm"
                            wire:click="increment({{ $field->id }}, {{ $detail->id }})">+</button>
                    </div>
                @endif
            </div>
        @endforeach

    </div>



    <style>
        .contanerDiv {
            border-radius: 12px;
            background: #9a9ea424;
            height: fit-content;
        }

        .radio-group {
            display: flex;
            /*gap: 10px;*/
            flex-wrap: wrap;
        }

        .radio-btn {
            display: none;
        }

        .radio-label {
            padding: 10px 15px;
            border: 1px solid #991D9C;
            border-radius: 8px;
            cursor: pointer;
            color: #991D9C;
            background-color: white;
            transition: all 0.3s ease;
            text-align: center;
            width: 100%;
        }

        .radio-btn:checked+.radio-label {
            background-color: #991D9C;
            color: white;
            box-shadow: 0 0 6px rgba(153, 29, 156, 0.5);
        }

        .radio-labels {
            margin: 15px 0 5px 0;
        }
    </style>

</div>
