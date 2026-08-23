<div>
    <div class="row mt-5 pt-5">
        <label  class="mt-3 mb-1">
           بارگذاری تصویر 
        </label>
        <p>در صوت نیاز می‌توانید با بارگذاری تصویر در این قسمت به تشخیص بهتر تکنسین‌ها در رابطه با خدمت مورد نظر خود کمک کنید.</p>
        @foreach (range(0, 0) as $i)
            <div class="col-md-3 mb-3">
                <div class="border p-2 text-center" style="height: 150px; position: relative;">
                    @if (!isset($previews[$i]))
                        <input type="file" wire:model="images.{{ $i }}" class="d-none" id="upload-{{ $i }}" accept="image/*">
                        <label for="upload-{{ $i }}" class="d-flex flex-column justify-content-center align-items-center h-100 cursor-pointer">
                            <i class="fas fa-plus"></i>
                            <small>تصویر {{ $i + 1 }}</small>
                        </label>
                        @error("images.$i") <span class="text-danger small">{{ $message }}</span> @enderror
                    @else
                        <img src="{{ $previews[$i] }}" class="w-100 h-100 object-fit-cover rounded" alt="Preview">
                        <button type="button" wire:click="removeImage({{ $i }})" class="btn btn-sm btn-danger position-absolute top-0 start-0 m-1">
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <style>
        .cursor-pointer { cursor: pointer; }
    </style>
</div>
