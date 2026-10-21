{{-- ویرایش سفارش: استپر و مراحل فرآیند بالای صفحه، فرم ویرایش و رابطه‌ها به‌صورت تب زیر آن --}}
<x-filament-panels::page
    @class([
        'fi-resource-edit-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>
    {{ $this->workflowInfolist }}

    @capture($form)
        <x-filament-panels::form
            id="form"
            :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
            wire:submit="save"
        >
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    @endcapture

    <x-filament-panels::resources.relation-managers
        :active-locale="isset($activeLocale) ? $activeLocale : null"
        :active-manager="$this->activeRelationManager"
        :content-tab-label="$this->getContentTabLabel()"
        :content-tab-icon="$this->getContentTabIcon()"
        :content-tab-position="$this->getContentTabPosition()"
        :managers="$this->getRelationManagers()"
        :owner-record="$record"
        :page-class="static::class"
    >
        <x-slot name="content">
            {{ $form() }}
        </x-slot>
    </x-filament-panels::resources.relation-managers>

    <x-filament-panels::page.unsaved-data-changes-alert />
</x-filament-panels::page>
