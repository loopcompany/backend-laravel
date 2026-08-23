<div class="mt-4">

    <h6 class="input-title p-3 mt-5">{{ $field->title }} {{$field->is_required ? '*' : ''}}
    <br>
    <small>{{ $field->des }}</small></h6>

    @foreach ($field->field_details as $detail)
        <div class="form-check form-checkbox d-flex">
            <input type="checkbox" class="form-check-input" id="field_{{ $detail->id }}"
                wire:change="updateCheckbox({{ $field->id }}, {{ $detail->id }}, $event.target.checked)"
                @checked($detail->is_checked == 1 || $this->is_checked($field->id, $detail->id))>

            <label class="form-check-label" for="field_{{ $detail->id }}">
                {{ $detail->title }}
                @if ($detail->price && $detail->show_price)
                    <span style="color:#FFA801; white-space: nowrap;">
                        ({{ number_format($this->getCurrentValue($detail->id) > 0 ? $detail->price * $this->getCurrentValue($detail->id) : $detail->price) }}
                        تومان)
                    </span>
                @endif
                @if ($this->is_checked($field->id, $detail->id) && !empty($detail->des))
                    <div class=" mt-2 small" style="color: #727272;">
                        {{ $detail->des }}
                    </div>
                @endif
                
            </label>
            
            @if ($detail->has_counter && $this->is_checked($field->id, $detail->id))
                <div class="counter-controls mt-2 mb-2 d-flex align-items-center gap-2" style="margin-right: auto;">
                    <button type="button" class="btn btn-sm btn-secondary"
                        wire:click="decrement({{ $field->id }}, {{ $detail->id }})">-</button>
                    <span>{{ $count[$detail->id] }}</span>
                    <button type="button" class="btn btn-sm btn-secondary"
                        wire:click="increment({{ $field->id }}, {{ $detail->id }})">+</button>
                </div>
            @endif
        </div>

    @endforeach

    <style>
        .form-check-input:checked {
            background-color: #991D9C;
            border-color: #991D9C;
            box-shadow: 0 0 5px rgba(200, 0, 255, 0.6);
        }

        input[type="checkbox"].form-check-input {
            width: 23px;
            height: 23px;
            border-radius: 50%;
            border: 1px solid #991D9C;
        }

        .form-check-label {
            margin-right: 7px;
            padding: 0 25px;
        }

        .form-checkbox {
            border: solid 1px #9a009c;
            padding: 7px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            margin-top: 10px;
            justify-content: flex-start;
            
        }
    </style>
</div>
