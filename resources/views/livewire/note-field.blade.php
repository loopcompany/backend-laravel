<div class="mb-4">
    @if ($field->title)
        <label class="form-label">{{ $field->title }}***</label>
    @endif

    <textarea class="form-control" rows="4" wire:model.live="formData.note.value"
        placeholder="{{ 'توضیحات خود را وارد کنید...' }}"></textarea>

    @if ($field->des)
        <small class="text-muted">{{ $field->des }}</small>
    @endif
</div>
