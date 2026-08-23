<div>

    <div>
        <div class="form-check form-checkbox">
            {{-- <input type="checkbox" class="form-check-input" id="field_{{ $field->id }}" wire:model="isChecked"> --}}
            <input type="checkbox" class="form-check-input" id="field_{{ $field->id }}"
                wire:change="updateCheckbox($event.target.checked)">
            <label for="field_{{ $field->id }}" class="mx-1">{{ $field->title }}</label>
        </div>
    </div>


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
