@php
    use App\Filament\Resources\OrderResource\Workflow\OrderWorkflowPanel;

    $files = OrderWorkflowPanel::userFiles($getRecord());
@endphp

<style>
    .oui-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
    .oui-item { display: block; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; text-decoration: none; transition: box-shadow .15s, transform .15s; }
    .oui-item:hover { box-shadow: 0 8px 20px -8px rgba(15, 23, 42, .35); transform: translateY(-2px); }
    .oui-thumb { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; display: block; background: #e2e8f0; }
    .oui-file { width: 100%; aspect-ratio: 1 / 1; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 13px; font-weight: 700; }
    .oui-cap { padding: 6px 10px; font-size: 11.5px; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .oui-empty { color: #94a3b8; font-size: 13px; padding: 8px 0; }
</style>

@if (empty($files))
    <div class="oui-empty">کاربر هنگام ثبت سفارش تصویر یا فایلی ارسال نکرده است.</div>
@else
    <div class="oui-grid">
        @foreach ($files as $file)
            <a class="oui-item" href="{{ $file['url'] }}" target="_blank" rel="noopener">
                @if ($file['is_image'])
                    <img class="oui-thumb" src="{{ $file['url'] }}" alt="{{ $file['label'] }}" loading="lazy">
                @else
                    <div class="oui-file">{{ strtoupper($file['extension'] ?: 'فایل') }}</div>
                @endif
                <div class="oui-cap">{{ $file['label'] }}</div>
            </a>
        @endforeach
    </div>
@endif
