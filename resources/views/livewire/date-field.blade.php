<div>
<h6 class="input-title p-3 mt-5">{{ $field->title }} *</h6>
<div class="radio-group">
    @foreach ($dateOptions as $index => $date)
        <div class="align-items-center d-flex flex-column contanerDiv">
            <input type="radio" class="radio-btn" id="detail_{{ $index }}" value="{{ $date }}"
                name="radio_group_{{ $field->id }}"
                @if (!$isUrgent && $field->is_required && $index == 0) required @endif
                wire:change="radioSelected('{{ $date }}')">
            <label class="radio-label" for="detail_{{ $index }}">
                {{ $date }}
            </label>
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
