<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class NeshanMapPicker extends Field
{
    protected string $view = 'components.forms.neshan-map-picker';

    protected string $latitudeField = 'latitude';
    protected string $longitudeField = 'longitude';

    public function latitude(string $field): static
    {
        $this->latitudeField = $field;
        return $this;
    }

    public function longitude(string $field): static
    {
        $this->longitudeField = $field;
        return $this;
    }

    public function getLatitudeField(): string
    {
        return $this->latitudeField;
    }

    public function getLongitudeField(): string
    {
        return $this->longitudeField;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);

        $this->afterStateHydrated(function (NeshanMapPicker $component, $state) {
            $record = $component->getRecord();

            if ($record) {
                $lat = data_get($record, $component->getLatitudeField());
                $lng = data_get($record, $component->getLongitudeField());

                if ($lat && $lng) {
                    $component->state([
                        'latitude' => $lat,
                        'longitude' => $lng,
                    ]);
                }
            }
        });

        $this->afterStateUpdated(function (NeshanMapPicker $component, $state, $set) {
            if (is_array($state) && isset($state['latitude'], $state['longitude'])) {
                $set($component->getLatitudeField(), $state['latitude']);
                $set($component->getLongitudeField(), $state['longitude']);
            }
        });
    }
}
