@extends('layout.main.header')
@section('content')
    <picture class="privacy-hero">
        <source
            media="(max-width: 767px)"
            srcset="{{ asset('assets/new-style/mobile/privacy.png') }}"
            width="1024"
            height="1536"
        >
        <img
            src="{{ asset('assets/new-style/privacy.png') }}"
            alt="سیاست حریم خصوصی لوپ"
            width="1535"
            height="1024"
            fetchpriority="high"
        >
    </picture>
    <div class="privacy_area pt-90 pb-90">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section_title text_center mb-50">
                        <div class="section_main_title">
                            <h1>سیاست حریم خصوصی لوپ</h1>
                        </div>
                        <div class="section_title_text pt-3">
                            <p>ما در لوپ به حفظ حریم خصوصی و امنیت اطلاعات شخصی کاربران خود متعهد هستیم.</p>
                        </div>
                        <div class="em_bar">
                            <div class="em_bar_bg"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12">
                    @if($privacies->count() > 0)
                        <div class="privacy_content">
                            @foreach($privacies as $privacy)
                                <div class="privacy_item mb-5">
                                    <div class="privacy_title mb-3">
                                        <h3>{{ $privacy->title }}</h3>
                                    </div>
                                    <div class="privacy_description">
                                        <div class="text-justify">
                                            {!! nl2br(e($privacy->description)) !!}
                                        </div>
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    <hr class="my-4">
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="no_privacy_content text-center">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                اطلاعات حریم خصوصی در حال حاضر موجود نیست.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Contact Section for Privacy Questions -->
            <div class="row mt-5">
                <div class="col-lg-12">
                    <div class="privacy_contact_section bg-light p-4 rounded" style="background-color: #1d283a !important">
                        <div class="text-center">
                            <h4 class="mb-3">سوالی در مورد حریم خصوصی دارید؟</h4>
                            <p class="mb-3">اگر سوال یا نگرانی‌ای در مورد سیاست حریم خصوصی ما دارید، با ما تماس بگیرید.</p>
                            <a href="{{ route('web.contact') }}" class="btn btn-primary">
                                <i class="fa fa-envelope"></i> تماس با ما
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--==================================================-->
    <!----- End Privacy Policy Area ----->
    <!--==================================================-->

    <style>
        .privacy-hero,
        .privacy-hero img {
            display: block;
            width: 100%;
        }

        .privacy-hero img {
            height: auto;
        }

        .privacy_item {
            border-left: 4px solid #007bff;
            padding-left: 20px;
        }
        
        .privacy_title h3 { 
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .privacy_description { 
            line-height: 1.8;
            font-size: 15px;
        }
        
        .privacy_contact_section {
            border: 1px solid #dca82c;
        }
        
        .text-justify {
            text-align: justify;
        }
        
        @media (max-width: 768px) {
            .privacy_item {
                padding-left: 15px;
            }
        }
    </style>
@endsection
