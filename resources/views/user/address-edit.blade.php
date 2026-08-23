@extends('layout.header')
@section('content')
    <main>
        <script
            src="https://maps.googleapis.com/maps/api/js?key=AIzaSyADPqrZxArohzY5_rjf6XzkEry-uJqP2xU&callback=initMap&language=fa&region=IR&libraries=places">
        </script>
        @include('layout.user.nav')

        <section class="pt-0">
            <div class="container">
                <div class="row">

                    @include('layout.user.sidebar')


                    <div class="col-xl-9">

                        <div class="card border bg-transparent rounded-3">
                            <div class="card-body p-4">
                                <a href="{{ route('user.addresses') }}" class="btn btn-outline-warning mb-3">مدیریت
                                    آدرس‌ها</a>
                                <div class="accordion accordion-icon accordion-bg-light">




                                    <div class="accordion-item mb-3">

                                        <div id="collapseTwo" class="accordion-collapse collapse show"
                                            aria-labelledby="headingTwo">
                                            <div class="accordion-body mt-3">
                                                <form action="{{ route('user.address.update', $address->id) }}"
                                                    method="post">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="col-12 mt-3">
                                                        <label>عنوان</label>
                                                        <input type="text" name="title" value="{{ $address->title }}"
                                                            class="type2-input required form-control" />
                                                    </div>

                                                    <div class="col-12 mt-3">
                                                        <label>آدرس</label>
                                                        <textarea name="address" class="type2-input required form-control">{{ $address->address }}</textarea>
                                                    </div class="col-12 mt-3">

                                                    <div class="col-12 mt-3">
                                                        <select name="region" id="selectOPT"
                                                            class="type2-region required form-select js-choice z-index-9 choices__input">
                                                            <option value="">لطفا منطقه خود را انتخاب نمائید</option>

                                                            @foreach ($neighbourhoods as $neighbourhood)
                                                                <option value="{{ $neighbourhood->id }}"
                                                                    @if ($address->neighbourhood_id == $neighbourhood->id) selected @endif>
                                                                    {{ $neighbourhood->title }}</option>
                                                            @endforeach

                                                        </select>
                                                    </div>

                                                    <div class="col-12 mt-3">
                                                        <div id="map" style="width: 100%; height: 400px;"></div>
                                                        <input type="hidden" name="map" id="latLng"
                                                            class="type2-map required  mapinput" />
                                                        <div id="ControlerContainer"></div>
                                                    </div>

                                                    <input type="hidden" name="latitude" id="input-lat"
                                                        value="{{ $address->latitude }}" />
                                                    <input type="hidden" name="longitude" id="input-lng"
                                                        value="{{ $address->longitude }}" />


                                                    <button type="submit"
                                                        class="btn btn-sm btn-success-soft mb-0 mt-4">ثبت</button>

                                                </form>
                                            </div>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </section>
    </main>

    <style>
        .search_map_input {
            position: absolute;
            left: 0px;
            top: 0px;
            border: none;
            border-radius: 10px;
            padding: 10px 15px;
            margin: 10px;
            box-shadow: 1px 1px 5px #00000061;
        }

        .map-control {
            background-color: #fff;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
        }

        .map-control button {
            width: 100%;
            height: 30px;
            font-size: 16px;
        }
    </style>

    <script>
        function initMap() {
            const latField = document.getElementById('input-lat');
            const lngField = document.getElementById('input-lng');

            const initialLat = latField.value ?
                parseFloat(latField.value) :
                35.6895;
            const initialLng = lngField.value ?
                parseFloat(lngField.value) :
                51.3381;

            const map = new google.maps.Map(document.getElementById('map'), {
                center: {
                    lat: initialLat,
                    lng: initialLng
                },
                zoom: 16,
                mapTypeControl: false,
                streetViewControl: false
            });
            const ControlerContainer = document.getElementById('ControlerContainer');
            const input = document.createElement('input');
            input.placeholder = 'جستجوی مکان';
            input.classList.add('search_map_input');
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

            const autocomplete = new google.maps.places.Autocomplete(input);

            const currentPositionButton = document.createElement('button');
            currentPositionButton.textContent = 'موقعیت فعلی';
            currentPositionButton.classList.add('btn', 'btn-primary', 'py-1', 'mx-1');
            currentPositionButton.type = "button";
            ControlerContainer.append(currentPositionButton);

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

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry) {
                    window.alert("No geometry found");
                    return;
                }

                const location = place.geometry.location;
                map.setCenter(location);
            });


            let marker;

            if (latField.value && lngField.value) {
                marker = new google.maps.Marker({
                    position: {
                        lat: initialLat,
                        lng: initialLng
                    },
                    map: map
                });
            }


            map.addListener('click', (event) => {
                if (marker) marker.setMap(null);

                marker = new google.maps.Marker({
                    position: event.latLng,
                    map: map
                });

                // ذخیره در فیلدهای مخفی
                document.getElementById('input-lat').value = event.latLng.lat();
                document.getElementById('input-lng').value = event.latLng.lng();
            });

            const resetMarkerButton = document.createElement('button');
            resetMarkerButton.textContent = 'انتخاب مجدد';
            resetMarkerButton.classList.add('btn', 'btn-info', 'py-1', 'mx-1');
            resetMarkerButton.type = "button";
            ControlerContainer.append(resetMarkerButton);
            resetMarkerButton.addEventListener('click', () => {
                if (marker) {
                    marker.setMap(null);
                    document.getElementById('latLng').value = '';
                    document.getElementById('input-lat').value = '';
                    document.getElementById('input-lng').value = '';
                }
            });
        }
        initMap();

        // ('.search_map_input').parent().css("display", "flex")
    </script>
@endsection
