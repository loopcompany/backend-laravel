<?php

namespace App\Services;

use App\Repositories\StepRepository;
use App\Models\Category;
use App\Models\ServiceSchedule;
use Carbon\Carbon;

class StepService
{
    public function __construct(private StepRepository $repo)
    {
    }

    public function fetchSteps(int $categoryId, ?string $accountType = null)
    {
        $steps = $this->repo->getCategoryFieldsGrouped($categoryId);

        $data = $this->transformGroupedFields($steps);

        // static steps
        $dateTimeStep = $this->dateTimeStep();
        $addressStep = $this->addressStep();
        $descriptionStep = $this->descriptionStep();

        // مرحله زمان نگهداری و سرویس فقط برای سازمان‌ها - اضافه شدن به انتهای مراحل پویا
        if (($accountType === 'organization' || $accountType === 'company' || $accountType === 's_g_organization' || $accountType === 'g_organization') && $categoryId == 3) {
            $serviceScheduleStep = $this->serviceScheduleStep();
            $data->push($serviceScheduleStep);
        } else {
            $data->push($dateTimeStep);
        }

        // insert static steps at the end to keep all dynamic category fields together

        $data->push($addressStep);

        // Get category from already loaded relationship to avoid N+1 query
        $category = $steps->flatten(1)->first()?->category;
        if ($category) {
            $data->push($this->genderStep($category->has_gender));
        }

        $data->push($descriptionStep);

        return $data;
    }

    public function fetchConditionalSteps(int $categoryId, int $fieldId, int $fieldDetailId)
    {
        $conditional = $this->repo->getConditionalFieldsGrouped($categoryId, $fieldId, $fieldDetailId);

        return $this->transformGroupedFields($conditional, true);
    }

    private function transformGroupedFields($grouped, $isConditional = false)
    {
        return $grouped->map(function ($items) use ($isConditional) {
            return $items->map(function ($item) use ($isConditional) {
                $field = $item->field;
                $field->is_conditional = $isConditional ? 0 : $item->is_conditional;

                $field->field_details = $field->field_details->map(function ($detail) use ($field) {
                    if (in_array($field->type, ['checkbox', 'radioButton'])) {
                        $detail->value = $detail->is_checked == 1 ? 1 : 0;
                    } elseif ($field->type == 'counter') {
                        $detail->value = 0;
                        $detail->now_server = now()->toIso8601String();
                        $detail->end_at = Carbon::parse($detail->end_at)
                            ->setTimezone(config('app.timezone'))
                            ->toIso8601String();

                    } elseif ($field->type == 'input') {
                        $detail->value = '';
                    }
                    $detail->type = $field->type;
                    $detail->user_descriptions = '';
                    return $detail;
                });
                return $field;
            })->unique('id');
        })->values();
    }

    private function dateTimeStep()
    {
        return collect([
            // [
            //     'id' => 'urgent',
            //     'title' => 'سفارش فوری',
            //     'type' => 'urgent',
            //     'is_required' => 0,
            //     'icon_name' => 'flash',
            //     'des' => 'درصورتی که نیاز به اجرای سفارش به صورت فوری دارید این گزینه را فعال کنید. تکنسین‌ها لوپ در اولین فرصت سفارش شما را بررسی و اجرا می‌کنند.',
            //     'value' => 0,
            // ],
            [
                'id' => 'date',
                'title' => __("Suitable date for order execution"),
                'type' => 'date',
                'is_required' => 1,
                'icon_name' => 'folders/schedule.webp',
                'des' => '',
                'value' => null,
            ],
            [
                'id' => 'time',
                'title' => __("Suitable time for order execution"),
                'type' => 'time',
                'is_required' => 1,
                'icon_name' => 'folders/schedule.webp',
                'des' => '',
                'value' => null,
            ],
        ]);
    }

    private function addressStep()
    {
        return collect([
            [
                'id' => 'address',
                'title' => __("Address for order execution"),
                'type' => 'address',
                'is_required' => 1,
                'icon_name' => 'map',
                'des' => '',
                'value' => null,
            ],
        ]);
    }

    private function genderStep($is_required)
    {
        return collect([
            [
                'id' => 'gender',
                'title' => __("Technician's gender"),
                'type' => 'gender',
                'is_required' => $is_required,
                'icon_name' => 'folders/tech-gender.webp',
                'des' => $is_required ? __("Select the gender of the technician for your service. Note that if a female technician is required, the presence of another female at the location is mandatory.") : __("Comming soon"),
                'value' => 0,
            ]
        ]);
    }

    private function descriptionStep()
    {
        return collect([
            [
                'id' => 'note',
                'title' => __("Final notes"),
                'type' => 'note',
                'is_required' => 0,
                'icon_name' => null,
                'des' => __("For example, coordination methods or more details ..."),
                'value' => null,
            ],
            [
                'id' => 'file',
                'title' => __("Upload Image"),
                'type' => 'file',
                'is_required' => 0,
                'icon_name' => 'folders/upload.webp',
                'des' => __("If needed, you can help better identify your order by uploading an image in this section."),
                'value' => null,
            ],
        ]);
    }

    private function serviceScheduleStep()
    {
        // دریافت گزینه‌های اصلی (کوتاه مدت / بلند مدت)
        $mainOptions = ServiceSchedule::active()
            ->ofType('main')
            ->ordered()
            ->get(['id', 'label', 'value', 'term_type'])
            ->map(function ($item) {
                return [
                    'id' => $item->value,
                    'title' => $item->label,
                    'value' => 0,
                    'is_checked' => 0,
                ];
            });

        // دریافت گزینه‌های مدت زمان برای بلند مدت
        $longTermDurations = ServiceSchedule::active()
            ->ofType('duration')
            ->forTerm('long_term')
            ->ordered()
            ->get(['id', 'label', 'value'])
            ->map(function ($item) {
                return [
                    'id' => $item->value,
                    'title' => $item->label,
                    'value' => 0,
                    'is_checked' => 0,
                ];
            });

        // دریافت گزینه‌های ساعت برای بلند مدت
        $longTermTimes = ServiceSchedule::active()
            ->ofType('time')
            ->forTerm('long_term')
            ->ordered()
            ->get(['id', 'label', 'value', 'start_time'])
            ->map(function ($item) {
                return [
                    'id' => $item->value,
                    'title' => $item->label,
                    'value' => 0,
                    'is_checked' => 0,
                    'start_time' => $item->start_time
                ];
            });

        // دریافت گزینه‌های ساعت برای کوتاه مدت
        $shortTermTimes = ServiceSchedule::active()
            ->ofType('time')
            ->forTerm('short_term')
            ->ordered()
            ->get(['id', 'label', 'value', 'start_time'])
            ->map(function ($item) {
                return [
                    'id' => $item->value,
                    'title' => $item->label,
                    'value' => 0,
                    'is_checked' => 0,
                    'start_time' => $item->start_time
                ];
            });

        return collect([
            [
                'id' => 'service_schedule',
                'title' => __("Service Schedule"),
                'type' => 'service_schedule',
                'is_required' => 1,
                'icon_name' => 'folders/schedule.webp',
                'des' => __("Select the type of service schedule"),
                'field_details' => [
                    [
                        'id' => 'main_selection',
                        'title' => __("Service Type"),
                        'type' => 'radioButton',
                        'options' => $mainOptions,
                    ],
                    [
                        'id' => 'long_term_duration',
                        'title' => __("Duration (Long Term)"),
                        'type' => 'radioButton',
                        'conditional_on' => 'long_term',
                        'options' => $longTermDurations,
                    ],
                    [
                        'id' => 'long_term_date',
                        'title' => __("Start Date (Long Term)"),
                        'type' => 'date',
                        'conditional_on' => 'long_term',
                        'value' => null,
                    ],
                    [
                        'id' => 'long_term_time',
                        'title' => __("Start Time (Long Term)"),
                        'type' => 'radioButton',
                        'conditional_on' => 'long_term',
                        'options' => $longTermTimes,
                    ],
                    // [
                    //     'id' => 'long_term_file',
                    //     'title' => 'بارگذاری فایل (بلند مدت)',
                    //     'type' => 'file',
                    //     'conditional_on' => 'long_term',
                    //     'value' => null,
                    // ],
                    [
                        'id' => 'short_term_date',
                        'title' => __("Start Date (Short Term)"),
                        'type' => 'date',
                        'conditional_on' => 'short_term',
                        'value' => null,
                    ],
                    [
                        'id' => 'short_term_time',
                        'title' => __("Start Time (Short Term)"),
                        'type' => 'radioButton',
                        'conditional_on' => 'short_term',
                        'options' => $shortTermTimes,
                    ],
                    // [
                    //     'id' => 'short_term_file',
                    //     'title' => 'بارگذاری فایل (کوتاه مدت)',
                    //     'type' => 'file',
                    //     'conditional_on' => 'short_term',
                    //     'value' => null,
                    // ],
                ],
            ],
        ]);
    }
}
