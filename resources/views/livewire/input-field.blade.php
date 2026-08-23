<div  class="mt-4">
    @if ($field->title)
        <h6 class="input-title p-3 mt-5">{{ $field->title }}</h6>
    @endif
    @foreach ($field->field_details as $detail)
        @php
            $inputKey = $field->id . '_' . $detail->id;
        @endphp

        <label for="field_{{ $inputKey }}" class="mt-3 mb-1">
            {{ $detail->title }}{{ $detail->is_required ? '*' : '' }}
        </label>
        @if ($detail->long_des??false)
            <textarea type="text" id="field_{{ $inputKey }}"
                class="form-control custom-textarea @error('formData.' . $inputKey) is-invalid @enderror"
                @if ($detail->is_required) required @endif wire:model.live="formData.{{ $inputKey }}" placeholder="مثلا نحوه هماهنگی یا جزییات بیشتر"></textarea>
        @else
            <input type="text" id="field_{{ $inputKey }}"
                class="form-control custom-input @error('formData.' . $inputKey) is-invalid @enderror"
                @if ($detail->is_required) required @endif wire:model.live="formData.{{ $inputKey }}">
        @endif
        @error('formData.' . $inputKey)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    @endforeach

    <style>
        .input-title {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #991D9C;
            font-size: 17px;
            background: #fdebf9;
            border-radius: 10px;
        }

        label {
            color: #2e2e2e
        }

        .custom-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: all 0.3s ease;
            outline: none;
        }
        .custom-textarea {
            width: 100%;
            padding: 10px 14px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: all 0.3s ease;
            outline: none;
            min-height: 150px !important
        }

        .custom-input:focus {
            border-color: #991D9C;
            box-shadow: 0 0 5px rgba(153, 29, 156, 0.4);
        }

        .custom-input::placeholder {
            color: #bbb;
        }
    </style>



</div>
