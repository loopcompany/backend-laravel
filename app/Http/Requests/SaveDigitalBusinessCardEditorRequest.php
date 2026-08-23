<?php

namespace App\Http\Requests;

use App\Models\DigitalBusinessCardBlock;
use App\Support\DigitalBusinessCardTypography;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class SaveDigitalBusinessCardEditorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $card = $this->route('card');

        return [
            'title' => ['required', 'string', 'max:191'],
            'slug' => [
                'required',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('digital_business_cards', 'slug')->ignore($this->currentCardId()),
            ],
            'des' => ['nullable', 'string'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'image_background' => ['nullable', 'string', 'max:2048'],
            'page_background_image' => ['nullable', 'string', 'max:2048'],
            'header_text_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'title_font_family' => ['nullable', 'string', 'max:50', Rule::in(array_merge([''], DigitalBusinessCardTypography::allowedFontKeys()))],
            'description_font_family' => ['nullable', 'string', 'max:50', Rule::in(array_merge([''], DigitalBusinessCardTypography::allowedFontKeys()))],
            'blocks' => ['required', 'array', 'min:1'],
            'blocks.*.id' => ['nullable', 'integer'],
            'blocks.*.temp_id' => ['nullable', 'string', 'max:100'],
            'blocks.*.type' => ['required', 'string', Rule::in(DigitalBusinessCardBlock::types())],
            'blocks.*.title' => 'required_unless:blocks.*.type,text,map',
            'blocks.*.sort' => ['nullable', 'integer', 'min:0'],
            'blocks.*.color' => ['nullable', 'string', 'max:20'],
            'blocks.*.background_color' => ['nullable', 'string', 'max:64'],
            'blocks.*.data' => ['nullable', 'array'],
            'blocks.*.data.descriptions' => ['nullable', 'string'],
            'blocks.*.data.link' => ['nullable', 'string', 'max:2048'],
            'blocks.*.data.image' => ['nullable', 'string', 'max:2048'],
            'blocks.*.data.animation_type' => ['nullable', 'string', 'max:100'],
            'blocks.*.data.icon' => ['nullable', 'string', 'max:2048'],
            'blocks.*.data.address' => ['nullable', 'string', 'max:191'],
            'blocks.*.data.latitude' => ['nullable', 'numeric'],
            'blocks.*.data.longitude' => ['nullable', 'numeric'],
            'blocks.*.data.typography' => ['nullable', 'array'],
            'blocks.*.data.items' => ['nullable', 'array'],
            'blocks.*.data.items.*.id' => ['nullable', 'integer'],
            'blocks.*.data.items.*.temp_id' => ['nullable', 'string', 'max:100'],
            'blocks.*.data.items.*.question' => ['nullable', 'string', 'max:1000'],
            'blocks.*.data.items.*.answer' => ['nullable', 'string'],
            'blocks.*.data.items.*.image' => ['nullable', 'string', 'max:2048'],
            'blocks.*.data.items.*.caption' => ['nullable', 'string', 'max:191'],
            'blocks.*.data.items.*.sort' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function currentCardId(): ?int
    {
        $card = $this->route('card');

        if ($card instanceof \App\Models\DigitalBusinessCard) {
            return $card->id;
        }

        return is_numeric($card) ? (int) $card : null;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ((array) $this->input('blocks', []) as $index => $block) {
                $type = data_get($block, 'type');
                $data = (array) data_get($block, 'data', []);
                $items = (array) data_get($data, 'items', []);

                // Validate background_color format
                $backgroundColor = data_get($block, 'background_color');
                if ($backgroundColor !== null && $backgroundColor !== '') {
                    if (!is_string($backgroundColor)) {
                        $validator->errors()->add("blocks.$index.background_color", 'رنگ پس زمینه بلاک معتبر نیست.');
                    } else {
                        $color = trim($backgroundColor);
                        $isValid =
                            $color === 'transparent' ||
                            preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color) ||
                            preg_match('/^rgb\(\s*(25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(25[0-5]|2[0-4]\d|1?\d?\d)\s*\)$/', $color) ||
                            preg_match('/^rgba\(\s*(25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(0|1|0?\.\d+)\s*\)$/', $color);

                        if (!$isValid) {
                            $validator->errors()->add("blocks.$index.background_color", 'رنگ پس زمینه بلاک معتبر نیست.');
                        }
                    }
                }

                if ($type === DigitalBusinessCardBlock::TYPE_TEXT && blank(data_get($data, 'descriptions'))) {
                    $validator->errors()->add("blocks.$index.data.descriptions", 'متن بلوک متنی الزامی است.');
                }

                if ($type === DigitalBusinessCardBlock::TYPE_LINK) {
                    if (blank(data_get($data, 'link'))) {
                        $validator->errors()->add("blocks.$index.data.link", 'لینک بلوک الزامی است.');
                    }
                }

                if ($type === DigitalBusinessCardBlock::TYPE_SOCIAL) {
                    if (blank(data_get($data, 'link'))) {
                        $validator->errors()->add("blocks.$index.data.link", 'لینک شبکه اجتماعی الزامی است.');
                    }
                }

                if ($type === DigitalBusinessCardBlock::TYPE_MAP) {
                    if (blank(data_get($data, 'link'))) {
                        $validator->errors()->add("blocks.$index.data.link", 'لینک نقشه الزامی است.');
                    }

                    if (blank(data_get($data, 'latitude'))) {
                        $validator->errors()->add("blocks.$index.data.latitude", 'عرض جغرافیایی الزامی است.');
                    }

                    if (blank(data_get($data, 'longitude'))) {
                        $validator->errors()->add("blocks.$index.data.longitude", 'طول جغرافیایی الزامی است.');
                    }
                }

                if ($type === DigitalBusinessCardBlock::TYPE_FAQ) {
                    if (empty($items)) {
                        $validator->errors()->add("blocks.$index.data.items", 'حداقل یک سوال و پاسخ برای FAQ لازم است.');
                    }

                    foreach ($items as $itemIndex => $item) {
                        if (blank(data_get($item, 'question'))) {
                            $validator->errors()->add("blocks.$index.data.items.$itemIndex.question", 'سوال الزامی است.');
                        }

                        if (blank(data_get($item, 'answer'))) {
                            $validator->errors()->add("blocks.$index.data.items.$itemIndex.answer", 'پاسخ الزامی است.');
                        }

                        if (data_get($item, 'sort') === null) {
                            $validator->errors()->add("blocks.$index.data.items.$itemIndex.sort", 'ترتیب سوال الزامی است.');
                        }
                    }
                }

                if ($type === DigitalBusinessCardBlock::TYPE_GALLERY) {
                    if (empty($items)) {
                        $validator->errors()->add("blocks.$index.data.items", 'حداقل یک تصویر برای گالری لازم است.');
                    }

                    foreach ($items as $itemIndex => $item) {
                        if (blank(data_get($item, 'image'))) {
                            $validator->errors()->add("blocks.$index.data.items.$itemIndex.image", 'تصویر گالری الزامی است.');
                        }

                        if (data_get($item, 'sort') === null) {
                            $validator->errors()->add("blocks.$index.data.items.$itemIndex.sort", 'ترتیب تصویر الزامی است.');
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان کارت الزامی است.',
            'header_text_color.regex' => 'رنگ متن هدر باید در فرمت HEX باشد.',
            'blocks.required' => 'بلاک‌ها الزامی هستند.',
            'blocks.array' => 'بلاک‌ها باید به صورت آرایه ارسال شوند.',
            'blocks.min' => 'حداقل یک بلاک باید ارسال شود.',
            'blocks.*.type.required' => 'نوع بلاک الزامی است.',
            'blocks.*.type.in' => 'نوع بلاک معتبر نیست.',
            'blocks.*.title.required' => 'عنوان بلاک الزامی است.',
            'slug.required' => 'اسلاگ کارت الزامی است.',
            'slug.max' => 'اسلاگ نمی‌تواند بیشتر از ۱۹۱ کاراکتر باشد.',
            'slug.regex' => 'اسلاگ فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.',
            'slug.unique' => 'این اسلاگ قبلاً برای کارت دیگری ثبت شده است.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');
        $this->merge([
            'slug' => $slug ? strtolower(trim(preg_replace('/\s+/', '-', $slug))) : null,
            'page_background_image' => $this->input('page_background_image'),
            'header_text_color' => $this->input('header_text_color'),
            'blocks' => collect($this->input('blocks', []))->map(function ($block) {
                $block['sort'] = isset($block['sort']) ? (int) $block['sort'] : null;
                $block['data'] = is_array($block['data'] ?? null) ? $block['data'] : [];
                $block['data']['typography'] = DigitalBusinessCardTypography::sanitizeTypography(
                    data_get($block, 'data.typography', [])
                );
                $block['data']['items'] = collect(data_get($block, 'data.items', []))->map(function ($item) {
                    if (isset($item['sort'])) {
                        $item['sort'] = (int) $item['sort'];
                    }

                    return $item;
                })->values()->all();

                return $block;
            })->values()->all(),
        ]);
    }
}
