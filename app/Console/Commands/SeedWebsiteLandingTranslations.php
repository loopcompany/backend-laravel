<?php

namespace App\Console\Commands;

use App\Models\LocalizationLanguage;
use App\Models\LocalizationMessage;
use App\Services\LocalizationCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SeedWebsiteLandingTranslations extends Command
{
    protected $signature = 'localization:seed-landing';
    protected $description = 'Enable the requested website languages and fill missing landing translations';

    public function handle(LocalizationCatalog $catalog): int
    {
        $languages = [
            'en' => ['انگلیسی', 'English', 'ltr'],
            'zh' => ['چینی ساده', '简体中文', 'ltr'],
            'ms' => ['مالایی', 'Bahasa Melayu', 'ltr'],
            'fr' => ['فرانسوی', 'Français', 'ltr'],
            'ar' => ['عربی', 'العربية', 'rtl'],
        ];
        $file = resource_path('localization/website-landing.json');
        $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (array_keys($languages) !== $data['locales']) {
            throw new RuntimeException('Unexpected landing translation locale order.');
        }

        $changed = DB::transaction(function () use ($catalog, $data, $languages) {
            $languageChanges = 0;
            $order = 1;
            foreach ($languages as $code => [$name, $nativeName, $direction]) {
                $language = LocalizationLanguage::where('code', $code)->lockForUpdate()->first();
                $values = ['is_enabled' => true, 'sort_order' => $order++, 'direction' => $direction];
                if (!$language) {
                    DB::table('localization_languages')->insert($values + [
                        'code' => $code, 'name' => $name, 'native_name' => $nativeName, 'direction' => $direction,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $languageChanges++;
                } elseif (!$language->is_enabled || (int) $language->sort_order !== $values['sort_order'] || $language->direction !== $direction) {
                    DB::table('localization_languages')->where('id', $language->id)->update($values + ['updated_at' => now()]);
                    $languageChanges++;
                }
            }

            $translationChanges = 0;
            foreach ($data['messages'] as $key => $translations) {
                if (count($translations) !== count($languages) || in_array('', $translations, true)) {
                    throw new RuntimeException('Incomplete landing translations for '.$key);
                }
                $hash = hash('sha256', $key);
                $row = LocalizationMessage::where('scope', 'website')->where('key_hash', $hash)->lockForUpdate()->first();
                $existing = $row?->translations ?? [];
                $merged = $existing;
                foreach (array_combine($data['locales'], $translations) as $locale => $translation) {
                    if (!isset($merged[$locale]) || trim($merged[$locale]) === '') {
                        $merged[$locale] = $translation;
                        $translationChanges++;
                    }
                }
                if ($merged === $existing) {
                    continue;
                }
                $catalog->validateMessage($row?->source_fa ?? $key, $merged);
                $values = ['translations' => json_encode($merged, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'updated_at' => now()];
                if ($row) {
                    DB::table('localization_messages')->where('id', $row->id)->update($values);
                } else {
                    DB::table('localization_messages')->insert($values + [
                        'scope' => 'website', 'message_key' => $key, 'key_hash' => $hash, 'source_fa' => $key, 'created_at' => now(),
                    ]);
                }
            }
            if ($languageChanges || $translationChanges) {
                $catalog->touch();
            }

            return $translationChanges;
        }, 3);

        $this->info("Landing translations filled: {$changed}.");

        return self::SUCCESS;
    }
}
