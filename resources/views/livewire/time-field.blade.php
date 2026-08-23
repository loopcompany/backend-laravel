<div>
    <h6 class="input-title p-3 mt-5">انتخاب ساعت</h6>
    
    @if(count($timeSlots) > 0)
        <div class="radio-group">
            @foreach ($timeSlots as $index => $slot)
                <div class="align-items-center d-flex flex-column contanerDiv">
                    <input type="radio" class="radio-btn" id="time_{{ $index }}" value="{{ $slot }}"
                     name="radio_group_{{ $field->id }}"
                        @if (!$isUrgent && $field->is_required && $index == 0) required @endif
                         wire:change="radioSelected('{{ $slot }}')">
                    <label class="radio-label" for="time_{{ $index }}">
                        {{ $slot }}
                    </label>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning">
            ساعت‌های کاری برای این دسته‌بندی تعریف نشده است.
        </div>
    @endif

    <style>
        .contanerDiv {
            border-radius: 12px;
            background: #9a9ea424;
            height: fit-content;
        }

        .radio-group {
            display: flex;
            gap: 10px;
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
