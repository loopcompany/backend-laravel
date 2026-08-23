@extends('layout.header')
@section('content')
    <main>

        @include('layout.user.nav')

        <section class="pt-0">
            <div class="container">
                <div class="row">

                    @include('layout.user.sidebar')


                    <div class="col-xl-9">
						
						<div class="card border bg-transparent rounded-3">
							<div class="card-body p-4">
								<a href="{{ route('user.address.add') }}" class="btn btn-outline-success mb-3">+ افزودن آدرس</a>
								<div class="accordion accordion-icon accordion-bg-light" id="accordionExample">
									
								
								@if(Auth::user()->addresses)
                                    @foreach(Auth::user()->addresses as $item)
                                
									<div class="accordion-item mb-3">
										<h6 class="accordion-header" id="headingTwo">
											<button 
                                                class="accordion-button rounded collapsed" 
                                                type="button"
												data-bs-toggle="collapse" 
                                                data-bs-target="#collapseTwo"
												aria-expanded="@if($loop->iteration == 1) true @else false @endif" 
                                                aria-controls="collapseTwo"
                                            >
												<span class="text-secondary fw-bold me-3">{{$loop->iteration}}</span>
												<span class="fw-bold">{{ $item->name }}</span>
											</button>
										</h6>
										<div id="collapseTwo" class="accordion-collapse collapse @if($loop->iteration == 1) show @endif" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
											<div class="accordion-body mt-3">
												
                                                
												<p class="mb-3"><b class="text-dark">عنوان: </b>
                                                    {{ $item->title }}
												</p>
												<p class="mb-3"><b class="text-dark">آدرس پستی: </b>
                                                    {{ $item->address }}
												</p>

												<p class="mb-3"><b class="text-dark">منطقه پستی : </b>
                                                    {{ $item->neighbourhood->title }}
												</p>
												
													<!--<div id="map{{ $item->id }}" style="width: 100%; height: 200px;" class="mb-4"></div>-->
													
													<!--            <script>-->
             <!--                                                   function initMap() {-->
             <!--                                                       const map = new google.maps.Map(document.getElementById('map{{ $item->id }}'), {-->
             <!--                                                           center: {-->
             <!--                                                               lat: {{ $item->latitude }},-->
             <!--                                                               lng: {{ $item->longitude }}-->
             <!--                                                           },-->
             <!--                                                           zoom: 16-->
             <!--                                                       });-->

             <!--                                                       let marker;-->
                                                                    <!--// let infoWindow = new google.maps.InfoWindow();-->
             <!--                                                       marker = new google.maps.Marker({-->
             <!--                                                           position: {-->
             <!--                                                               lat: {{ $item->latitude }},-->
             <!--                                                               lng: {{ $item->longitude }}-->
             <!--                                                           },-->
             <!--                                                           map: map-->
             <!--                                                       });-->
             <!--                                                   }-->
                                                                <!--// initMap({{ $item->id }});-->
             <!--                                               </script>-->
											
												<iframe
                                                    width="100%"
                                                    height="450"
                                                    style="border:0"
                                                    loading="lazy"
                                                    allowfullscreen
                                                    referrerpolicy="no-referrer-when-downgrade"
                                                    src="https://www.google.com/maps?q={{ $item?->latitude }},{{ $item?->longitude }}&hl=fa&z=15&output=embed">
                                                </iframe>
                            
                            
										
												<a href="{{ route('user.address.delete', ['id' => $item->id]) }}">
													<button class="btn btn-danger-soft btn-sm mb-0">حذف</button>
												</a>
											</div>
										</div>
									</div>

								    @endforeach
								@endif

								</div>
							</div>
						</div>
					</div>


                </div>
            </div>
        </section>
    </main>
@endsection
