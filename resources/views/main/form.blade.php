@extends('layout.main.header')
@section('content')
    <main>

        <section class="bg-blue py-7">
            <div class="container">
                <div class="row justify-content-lg-between">
                    <div class="col-lg-8">
                        <h1 class="text-white fs-4">{{ $category->title }} لوپ </h1>
                        <p class="text-white">{{ $category->des }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="pt-0">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow rounded-2 p-0 mt-n5">


                            <div class="card-body p-sm-4">


                                <livewire:dynamic-form :cityName="$cityName" :categoryId="$category->id"
                                    wire:key="dynamic-form-{{ now()->timestamp }}" />

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <div class="modal fade" id="set_off_at" tabindex="-1" aria-labelledby="addNewcardLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-dark">
                        <h5 class="modal-title text-white" id="addNewcardLabel">تعیین موقعیت</h5>
                        <button type="button" class="btn btn-sm mb-0" style="color: #fff;" data-bs-dismiss="modal"
                            aria-label="Close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">

                        <div id="mapOrder" style="width: 100%; height: 400px;"></div>
                        <div id="ControlerContainers" class="mt-4" style=" display: flex; justify-content: center;"></div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success my-0" data-bs-dismiss="modal">ثبت و ادامه</button>
                        {{-- <a href="" class="btn btn-success my-0">ثبت و ادامه</a> --}}
                    </div>
                </div>
            </div>
        </div>


        <style>
            .search_map_input {
                position: absolute;
                left: 0;
                top: 0;
                border: none;
                border-radius: 10px;
                padding: 10px 15px;
                margin: 10px;
                box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.38);
                width: 250px;
                z-index: 1000;
            }

            #map {
                width: 100%;
                height: 400px;
                border-radius: 8px;
            }

            @media only screen and (min-width: 600px) {
                .currentLoc {
                    display: none;
                }
            }
        </style>


        <script>
            function discountcheck() {
                var code = document.getElementById('discount').value;
                console.log(code);
                var id = document.getElementById('idd').value;

                $.ajax({
                    url: "{{ config('app.url') }}/discount-check",
                    method: "POST",
                    data: {
                        id: id,
                        code: code,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        console.log(response, response.error);
                        document.getElementById('result').innerHTML = response.error;
                    }
                });


            }
        </script>
        
        <script>
            function initMap() {
                const map = new google.maps.Map(document.getElementById('mapOrder'), {
                    center: {
                        lat: 35.6895,
                        lng: 51.3381
                    },
                    zoom: 13,
                    mapTypeControl: false,
                    streetViewControl: false
                });


                ControlerContainer = document.getElementById('ControlerContainers');
                const input = document.createElement('input');
                input.placeholder = 'جستجوی مکان';
                input.classList.add('search_map_input');
                map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);


                const autocomplete = new google.maps.places.Autocomplete(input);

                const currentPositionButton = document.createElement('button');
                currentPositionButton.textContent = 'موقعیت فعلی';
                currentPositionButton.classList.add('btn', 'btn-primary', 'py-1', 'mx-1');
                // currentPositionButton.prop('type', 'button');
                currentPositionButton.type = "button";

                // map.controls[google.maps.ControlPosition.TOP_LEFT].push(currentPositionButton);
                ControlerContainer.append(currentPositionButton);

                // هنگامی که کاربر دکمه موقعیت فعلی را فشار می‌دهد، مرکز نقشه را به موقعیت فعلی کاربر تغییر می‌دهد
                currentPositionButton.addEventListener('click', () => {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            // ...
                        },
                        (error) => {
                            alert("برای استفاده از این ویژگی، لطفا به موقعیت مکانی خود دسترسی دهید.");
                        }
                    );
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(position => {
                            const location = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            map.setCenter(location);
                        });
                    } else {
                        window.alert('Geolocation is not supported by this browser.');
                    }
                });

                // هنگامی که کاربر جستجو می کند، مرکز نقشه را به مکان جستجو شده تغییر می دهد
                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (!place.geometry) {
                        window.alert("No geometry found");
                        return;
                    }

                    const location = place.geometry.location;
                    map.setCenter(location);
                });
                const service = new google.maps.places.PlacesService(map);

                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();

                    if (!place.place_id) {
                        alert("لطفاً یک مکان معتبر انتخاب کنید");
                        return;
                    }

                    service.getDetails({
                        placeId: place.place_id
                    }, (result, status) => {
                        if (status === 'OK' && result.geometry) {
                            map.setCenter(result.geometry.location);
                        } else {
                            alert("جزئیات مکان در دسترس نیست");
                        }
                    });
                });


                let marker;

                // ایجاد یک دکمه برای ریست کردن مارکر
                const resetMarkerButton = document.createElement('button');
                resetMarkerButton.textContent = 'انتخاب مجدد';
                resetMarkerButton.classList.add('btn', 'btn-info', 'py-1', 'mx-1');
                // resetMarkerButton.prop('type', 'button');
                resetMarkerButton.type = "button";
                // map.controls[google.maps.ControlPosition.TOP_LEFT].push(resetMarkerButton);
                ControlerContainer.append(resetMarkerButton);



                // هنگامی که کاربر روی نقشه کلیک می‌کند، یک مارکر جدید در آن نقطه ایجاد می‌شود
                map.addListener('click', (event) => {
                    if (marker) {
                        marker.setMap(null); // اگر مارکر قبلی وجود داشت، آن را حذف می‌کنیم
                    }
                    marker = new google.maps.Marker({
                        position: event.latLng,
                        map: map
                    });

                    document.getElementById('latLng').value = event.latLng;

                    document.getElementById('latLng').dispatchEvent(new Event('input'));


                });

                // هنگامی که کاربر دکمه ریست مارکر را فشار می‌دهد، مارکر فعلی حذف می‌شود
                resetMarkerButton.addEventListener('click', () => {
                    if (marker) {
                        marker.setMap(null);
                        document.getElementById('latLng').value = '';
                    }
                });


            }
        </script>
        
    </main>
@endsection