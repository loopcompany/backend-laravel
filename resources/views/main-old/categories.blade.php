@extends('layout.main.header')

@section('meta')
    @php
        use Illuminate\Support\Str;

        // بررسی وجود متغیر $check و تنظیم مقادیر پیش‌فرض
        $check =
            $check ??
            (object) [
                'title' => '',
                'meta_title' => '',
                'meta_description' => '',
                'seo_content' => '',
                'id' => null,
                'slug' => '',
                'image_path' => null,
                'created_at' => null,
                'updated_at' => null,
            ];

        // مقادیر پیش‌فرض
        $defaultTitle = 'خدمات تخصصی لوپ';
        $defaultDescription =
            'لوپ ارائه‌دهنده خدمات تخصصی تعمیر موبایل، لپ‌تاپ و تبلت در سراسر ایران. ثبت سفارش آنلاین با بهترین تعمیرکاران.';
        $defaultImage = asset('images/default-category.jpg');
        $siteLogo = asset('assets/images/logo.webp');
        $siteName = 'لوپ';

        // متغیرهای نهایی
        $metaTitle = $check->meta_title ?? ($check->title ?? $defaultTitle);
        $metaDescription =
            $check->meta_description ?? (Str::limit(strip_tags($check->seo_content), 160) ?? $defaultDescription);
        $url = isset($check->id)
            ? route('web.category.show', ['id' => $check->id, 'slug' => $check->slug ?? ''])
            : url()->current();
        $image = isset($check->image_path) ? asset('storage/' . $check->image_path) : $defaultImage;
        $publishedTime = optional($check->created_at)->toIso8601String();
        $modifiedTime = optional($check->updated_at)->toIso8601String();
    @endphp

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="index, follow">
    <meta name="author" content="{{ $siteName }}">
    <meta name="language" content="fa">
    <meta name="geo.placename" content="Tehran">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="fa_IR">
    <meta property="article:published_time" content="{{ $publishedTime }}">
    <meta property="article:modified_time" content="{{ $modifiedTime }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $image }}">
    <meta name="twitter:image:alt" content="{{ $metaTitle }}">

    {{-- Structured Data: BreadcrumbList --}}
    {{-- Breadcrumb Schema --}}
    @if (isset($check))
        @php
            $position = 2;
            $breadcrumbItems = [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'خانه',
                    'item' => url('/')
                ]
            ];

            // جمع‌آوری اجداد (دسته‌های بالاتر)
            $ancestors = collect([]);
            $parent = $check->parent ?? null;
            while ($parent) {
                $ancestors->prepend($parent);
                $parent = $parent->parent ?? null;
            }

            // اضافه کردن اجداد به breadcrumb
            foreach($ancestors as $ancestor) {
                $breadcrumbItems[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $ancestor->title,
                    'item' => route('web.category.show', ['id' => $ancestor->id, 'slug' => $ancestor->slug])
                ];
            }

            // اضافه کردن دسته فعلی
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $check->title,
                'item' => route('web.category.show', ['id' => $check->id, 'slug' => $check->slug])
            ];

            $breadcrumbSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbItems
            ];
        @endphp
        <script type="application/ld+json">
            {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endif

    {{-- FAQ Schema --}}
    @if (!empty($check->faq_schema))
        @php
            $faqSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => collect($check->faq_schema)
                    ->map(function ($faq) {
                        return [
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => $faq['answer'],
                            ],
                        ];
                    })
                    ->toArray(),
            ];
        @endphp
        <script type="application/ld+json">
            {!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endif



    {{-- Structured Data: Service --}}
    @php
        $serviceSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $metaTitle,
            'description' => strip_tags($metaDescription),
            'url' => $url,
            'image' => $image,
            'provider' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'logo' => $siteLogo,
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'availability' => 'https://schema.org/InStock',
                'priceCurrency' => 'IRR',
                'price' => '1000000',
                'validFrom' => $publishedTime ?? now()->toIso8601String(),
            ],
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($serviceSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endsection


@section('content')
    <main>
        {{-- بخش عنوان و breadcrumb --}}
        <section class="py-5">
            <div class="container text-center">
                <h2 class="fs-2">{{ $check->title }}</h2>

                @if (isset($sub_categories) && $sub_categories->isNotEmpty())
                    @php
                        $currentCategory = $sub_categories->first();
                        $parents = $currentCategory->getAllParents()->reverse();
                    @endphp

                    <nav aria-label="breadcrumb" class="category-breadcrumb mb-4">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item">
                                <a href="{{ route('web.category.show') }}">دسته‌بندی‌ها</a>
                            </li>
                            @foreach ($parents as $parent)
                                @if ($parent->id)
                                    <li class="breadcrumb-item">
                                        <a
                                            href="{{ route('web.category.show', ['id' => $parent->id, 'slug' => $parent->slug ?? '']) }}">
                                            {{ $parent->title }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ol>
                    </nav>
                @endif
            </div>

            @if (isset($sub_categories) && $sub_categories->isEmpty())
                <div class="col-12 text-center">
                    @if (isset($check) && $check->has_subcategory == 1)
                        <p class="text-muted">ثبت سفارش برای این خدمت در دسترس نمی‌باشد</p>
                    @else
                        <a class="btn btn-primary" href="{{ isset($check) && $check->id ? route('web.order.form', ['category_id' => $check->id]) : '#' }}">
                            ثبت سفارش
                        </a>
                    @endif
                </div>
            @endif

        </section>

        {{-- اسلایدر دسته‌بندی‌ها --}}
        @if(isset($headerCategories) && $headerCategories->isNotEmpty())
        <section class="pb-0">
            <div class="container">
                <div class="tiny-slider arrow-round arrow-creative arrow-blur arrow-hover py-1">
                    <div class="tiny-slider-inner" data-autoplay="true" data-gutter="80" data-arrow="true" data-dots="false"
                        data-items="5" data-items-lg="3" data-items-md="2" data-items-xs="1">
                        @foreach ($headerCategories as $cat)
                            <div>
                                <div class="bg-body text-center rounded-2 border py-2 px-1 position-relative"
                                    style="background-color: #ffa800 !important;">
                                    <img src="{{ asset('storage/' . $cat->image_path) }}" alt="{{ $cat->title }}"
                                        class="h-40px" width="40" height="40">

                                    <a href="{{ $cat->id ? route('web.category.show', ['id' => $cat->id, 'slug' => $cat->slug ?? '']) : '#' }}"
                                        class="text-primary-hover stretched-link">
                                        <p class="h6 ms-2 mt-3 text-white">{{ $cat->title }}</p>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
        @endif



        {{-- لیست زیرشاخه‌ها --}}
        <section class="position-relative pt-0 pt-lg-5">
            <div class="container">
                <div class="row g-4">
                    @if (isset($sub_categories) && $sub_categories->isEmpty())
                    @else
                        @if(isset($sub_categories))
                        @foreach ($sub_categories as $cat)
                            @php
                                $count = $cat->children()->count();
                                $link = $cat->id
                                    ? route('web.category.show', ['id' => $cat->id, 'slug' => $cat->slug ?? ''])
                                    : '#';
                                $label = $cat->category_labels ?? null;
                                $avgRate = method_exists($cat, 'averageUserRate') ? round($cat->averageUserRate(), 1) : 4.5;
                                $orders_count = method_exists($cat, 'ordersCountWithChildren') ? $cat->ordersCountWithChildren() : 0;
                            @endphp

                            <x-cards.category-card :category="$cat" :count="$count" :label="$label" :avgRate="$avgRate"
                                :orderscount="$orders_count" />
                        @endforeach
                        @endif
                    @endif
                </div>
                {{-- نمایش تصویر دسته‌بندی قبل از متن SEO --}}
                <section class="category-image-section mt-4 mb-4 text-center">
                    @if ($check->image_path)
                        <img src="{{ asset('storage/' . $check->image_path) }}" alt="{{ $check->title }}"
                            class="img-fluid rounded-3" style="max-height: 400px; object-fit: cover; width: 80%;">
                    @else
                        <p>عکس موجود نیست</p>
                    @endif
                </section>
                {{-- متن SEO پایین کارت‌ها --}}
                @if (!empty($check->seo_content))
                    <div class="seo-content mt-5 p-4 bg-light rounded-3">
                        {!! $check->seo_content !!}
                    </div>
                @endif
            </div>

            @if(isset($sub_categories) && method_exists($sub_categories, 'links'))
            <div class="mt-5 d-flex justify-content-center">
                {{ $sub_categories->links() }}
            </div>
            @endif
        </section>

    </main>
@endsection
