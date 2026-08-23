<label for="field_{{ $field->id }}">{{ $field->title }}</label>
<div style="display: flex; justify-content: center;">
    <img src="{{ asset('storage/'.$field->image_path) }}" style="height: 250px; border-radius: 10px;" />
</div>