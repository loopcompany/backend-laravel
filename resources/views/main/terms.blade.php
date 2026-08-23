@extends('layout.main.header')
@section('content')
    <picture class="terms-hero">
        <source
            media="(max-width: 767px)"
            srcset="{{ asset('assets/new-style/mobile/rule.png') }}"
            width="1024"
            height="1536"
        >
        <img
            src="{{ asset('assets/new-style/rule.png') }}"
            alt="قوانین و مقررات لوپ"
            width="1983"
            height="793"
            fetchpriority="high"
        >
    </picture>

    <div class="terms_area pt-90 pb-90">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section_title text_center mb-50">
                        <div class="section_main_title">
                            <h1>قوانین و شرایط استفاده از خدمات لوپ</h1>
                        </div>
                        <div class="section_title_text pt-3">
                            <p>لطفاً قوانین و مقررات زیر را به دقت مطالعه کنید. با استفاده از خدمات لوپ، شما این قوانین را
                                می‌پذیرید.</p>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    @if ($terms->count() > 0)
                        <div class="terms_content">
                            @foreach ($terms as $index => $term)
                                <div class="terms_item mb-5">
                                    <div class="terms_header mb-3">
                                        <div class="terms_number">
                                            <span class="badge badge-primary">{{ $index + 1 }}</span>
                                        </div>
                                        <div class="terms_title">
                                            <h3>{{ $term->title }}</h3>
                                        </div>
                                    </div>
                                    <div class="terms_description">
                                        <div class="text-justify">
                                            {!! nl2br(e($term->description)) !!}
                                        </div>
                                    </div>
                                </div>
                                @if (!$loop->last)
                                    <hr class="my-4">
                                @endif
                            @endforeach
                        </div>

                        <!-- Agreement Section -->
                        <div class="agreement_section mt-5 p-4 bg-light rounded">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="agreement_content text-center">
                                        <h4 class="mb-3">
                                            <i class="fa fa-check-circle text-success"></i>
                                            پذیرش قوانین و مقررات
                                        </h4>
                                        <p class="mb-3">
                                            با ثبت نام و استفاده از خدمات لوپ، شما تمامی قوانین و مقررات فوق را می‌پذیرید و
                                            متعهد به رعایت آن‌ها هستید.
                                        </p>
                                        <div class="agreement_actions">
                                            <a href="{{ route('web.contact') }}" class="btn btn-outline-primary mr-3">
                                                <i class="fa fa-envelope"></i> تماس با ما
                                            </a>
                                            <a href="{{ route('web.privacy') }}" class="btn btn-outline-secondary">
                                                <i class="fa fa-shield"></i> حریم خصوصی
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="no_terms_content text-center">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                قوانین و مقررات در حال حاضر موجود نیست.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Last Updated Section -->
            @if ($terms->count() > 0)
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="last_updated_section text-center">
                            <small class="text-muted">
                                <i class="fa fa-clock-o"></i>
                                آخرین به‌روزرسانی:
                                {{ $terms->max('updated_at') ?  Morilog\Jalali\Jalalian::fromDateTime($terms->max('updated_at'))->format('Y/m/d') : 'نامشخص' }}
                            </small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <!--==================================================-->
    <!----- End Terms and Conditions Area ----->
    <!--==================================================-->

    <style>
        .terms-hero,
        .terms-hero img {
            display: block;
            width: 100%;
        }

        .terms-hero img {
            height: auto;
        }

        .terms_item {
            border: 1px solid #dca82c;
            border-radius: 8px;
            padding: 25px;
            background: #1d283a;
            transition: all 0.3s ease;
        }

        .terms_item:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-color: #007bff;
        }

        .terms_header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .terms_number {
            margin-left: 15px;
        }

        .terms_number .badge {
            font-size: 16px;
            padding: 8px 12px;
            border-radius: 50px;
        }

        .terms_title h3 {
            font-weight: 600;
            margin: 0;
            font-size: 20px;
        }

        .terms_description {
            line-height: 1.8;
            font-size: 15px;
            padding-right: 45px;
        }

        .agreement_section {
            border: 2px solid #dca82c;
            background: #1d283a !important;
        }

        .agreement_actions .btn {
            margin: 5px;
        }

        .text-justify {
            text-align: justify;
        }

        .last_updated_section {
            padding: 15px; 
            border-radius: 5px; 
        }

        @media (max-width: 768px) {
            .terms_description {
                padding-right: 0;
            }

            .terms_header {
                flex-direction: column;
                align-items: flex-start;
            }

            .terms_number {
                margin-left: 0;
                margin-bottom: 10px;
            }
        }
    </style>
@endsection