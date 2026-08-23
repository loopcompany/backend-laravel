@extends('layout.main.header')
@section('content')
	<!-- **************** MAIN CONTENT START **************** -->
	<main>

		<!-- =======================
Page Banner START -->
		<section class="pt-5 pb-0"
			style="background-image:url(assets/images/element/map.svg); background-position: center left; background-size: cover;">
			<div class="container">
				<div class="row">
					<div class="col-lg-8 col-xl-6 text-center mx-auto">
						<!-- Title -->
						<h6 class="text-primary">تماس با ما</h6>
						<h1 class="mb-4 fs-4">ما برای خدمت‌رسانی به شما آماده هستیم!</h1>
					</div>
				</div>

				<!-- Contact info box -->
				<div class="row g-4 g-md-5 mt-0 mt-lg-3">
					<!-- Box item -->


					<!-- Box item -->
                    @php
                        $phones = $contacts->where('type', 'phone');
                        $emails = $contacts->where('type', 'email');
                        $offices = $contacts->where('type', 'office');
                    @endphp

                    {{-- نمایش اطلاعات تماس (تلفن و ایمیل) --}}
                    @if($phones->count() || $emails->count())
                    <div class="col-lg-4 mt-lg-0">
                        <div class="card card-body shadow py-5 text-center h-100">
                            <h4 class="fw-normal">اطلاعات تماس</h4>
                            <hr>
                            <ul class="list-inline mb-0 mt-2">
                                @foreach ($phones as $contact)
                                <li class="list-item mb-3 h6 fw-light" style="text-align:right; padding-right: 50px;">
                                    <a href="{{ $contact->link }}">
                                        <i class="fas fa-fw fa-phone-alt me-2"></i>{{ $contact->title }}: {{ $contact->name }}
                                    </a>
                                </li>
                                @endforeach

                                @foreach ($emails as $contact)
                                <li class="list-item mb-3 h6 fw-light" style="text-align:right; padding-right: 50px;">
                                    <a href="{{ $contact->link }}">
                                        <i class="fas fa-fw fa-envelope me-2"></i>{{ $contact->title }}: {{ $contact->name }}
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    {{-- نمایش آدرس دفتر --}}
                    @if($offices->count())
                    <div class="col-lg-4 mt-lg-0">
                        <div class="card card-body bg-primary shadow py-5 text-center h-100">
                            <h4 class="text-white mb-3 fw-normal">آدرس لوپ</h4>
                            <hr>
                            <ul class="list-inline mb-0 mt-2">
                                @foreach ($offices as $contact)
                                <li class="list-item mb-3" style="text-align:right; padding-right: 50px; color: white;">
                                    <i class="fas fa-fw fa-map-marker-alt me-2 mt-1" style="color: white !important;"></i>
                                    {{ $contact->title }}: {{ $contact->name }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif


                    @if(count($socials))
					<!-- Box item -->
					<div class="col-lg-4 mt-lg-0">
						<div class="card card-body shadow py-5 text-center h-100">
							<!-- Title -->
							<h5 class="mb-3 fw-normal">فضا‌های مجازی لوپ</h5>
							<hr>
							<ul class="list-inline mb-0 mt-2">
								@foreach($socials as $social)
									<li class="list-item mb-3 h6 fw-light" style="text-align:right; padding-right: 50px;">
										<a href="{{$social->link}}">{{$social->title}}:
											{{$social->value}}
										</a>
									</li>
                                @endforeach
							</ul>
						</div>
					</div>
                    @endif
				</div>
			</div>
		</section>
		<!-- =======================
Page Banner END -->

		<!-- =======================
Image and contact form START -->
		<section>
			<div class="container">
				<div class="row g-4 g-lg-0 align-items-center">

					<div class="col-md-6 align-items-center text-center">
						<!-- Image -->
						<img src="assets/images/element/contact.svg" class="h-400px" alt="">

						<!-- Social media button -->
						<div class="d-sm-flex align-items-center justify-content-center mt-2 mt-sm-4">
							<h5 class="mb-0">ما را دنبال کنید:</h5>
							<ul class="list-inline mb-0 ms-sm-2">
								@foreach ($socials as $social)
                                <li class="list-inline-item">
                                    <a alt="{{$social->title}}" class="fs-5 me-1" href="{{$social->link}}" title="{{$social->title}}">
                                        <img alt="{{$social->title}} لوپ" src="{{asset('storage/'.$social->icon )}}" style="width:25px; height:25px" />
                                    </a>
                                </li>
                                @endforeach

								<li class="list-inline-item"> <a class="fs-5 me-1 text-dribbble" aria-label="Just fo the fun of it" href="#"></a> </li>

							</ul>
						</div>
					</div>

					<!-- Contact form START -->
					<div class="col-md-6">
						<!-- Title -->
						<h2 class="mt-4 mt-md-0 fs-4">با ما در ارتباط باشید</h2>
						<p>برای ما پیغام بگذارید، کارشناسان لوپ در اسرع وقت با شما تماس خواهند گرفت</p>

						<!-- Success Message -->
						@if(session('success'))
						<div class="alert alert-success alert-dismissible fade show" role="alert">
							<i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
						@endif

						<!-- Error Message -->
						@if(session('error'))
						<div class="alert alert-danger alert-dismissible fade show" role="alert">
							<i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
						@endif

						<!-- Validation Error Messages -->
						@if($errors->any())
						<div class="alert alert-danger alert-dismissible fade show" role="alert">
							<i class="bi bi-exclamation-triangle-fill me-2"></i>
							<ul class="mb-0">
								@foreach($errors->all() as $error)
								<li>{{ $error }}</li>
								@endforeach
							</ul>
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
						@endif

						<form action="{{route('submit.contact')}}" method="post">
                            @csrf
							<!-- Name -->
							<div class="mb-4 bg-light-input">
								<label for="yourName" class="form-label">نام و نام خانوادگی *</label>
								<input name="name" type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" 
									id="yourName" value="{{ old('name') }}" required>
								@error('name')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<!-- Email -->
							<div class="mb-4 bg-light-input">
								<label for="emailInput" class="form-label">ایمیل *</label>
								<input name="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" 
									id="emailInput" value="{{ old('email') }}" required>
								@error('email')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<!-- Phone -->
							<div class="mb-4 bg-light-input">
								<label for="phoneInput" class="form-label">شماره تلفن همراه *</label>
								<input name="phone" type="text" class="form-control form-control-lg @error('phone') is-invalid @enderror" 
									id="phoneInput" value="{{ old('phone') }}" required>
								@error('phone')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>

                            <div class="mb-4 bg-light-input">
								<label for="yourtitle" class="form-label">عنوان *</label>
								<input name="title" type="text" class="form-control form-control-lg @error('title') is-invalid @enderror" 
									id="yourtitle" value="{{ old('title') }}" required>
								@error('title')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<!-- Message -->
							<div class="mb-4 bg-light-input">
								<label for="textareaBox" class="form-label">متن درخواست *</label>
								<textarea name="message" class="form-control @error('message') is-invalid @enderror" 
									id="textareaBox" rows="4" required>{{ old('message') }}</textarea>
								@error('message')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<!-- Button -->
							<div class="d-grid">
								<button class="btn btn-lg btn-primary mb-0" type="submit">ارسال</button>
							</div>
						</form>
					</div>
					<!-- Contact form END -->
				</div>
			</div>
		</section>

	</main>
	<!-- **************** MAIN CONTENT END **************** -->
@endsection
