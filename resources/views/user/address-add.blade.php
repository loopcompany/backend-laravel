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
                                <a href="{{ route('user.addresses') }}" class="btn btn-outline-warning mb-3">مدیریت
                                    آدرس‌ها</a>
                                <div class="accordion accordion-icon accordion-bg-light">




                                    <div class="accordion-item mb-3">

                                        <div id="collapseTwo" class="accordion-collapse collapse show"
                                            aria-labelledby="headingTwo">
                                            <div class="accordion-body mt-3">
                                                <form action="{{ route('user.address.create') }}" method="post">
                                                    @csrf
                                                    <div class="col-12 mt-3">
                                                        <label>عنوان</label>
                                                        <input type="text" name="title"
                                                            class="type2-input required form-control" />
                                                    </div>

                                                    <div class="col-12 mt-3">
                                                        <label>آدرس</label>
                                                        <textarea name="address" class="type2-input required form-control"></textarea>
                                                    </div class="col-12 mt-3">

                                                    <div class="col-12 mt-3">
                                                        <select name="region" id="selectOPT"
                                                            class="type2-region required form-select js-choice z-index-9 choices__input">
                                                            <option value="">لطفا منطقه خود را انتخاب نمائید</option>

                                                            @foreach ($neighbourhoods as $neighbourhood)
                                                                <option value="{{ $neighbourhood->id }}">
                                                                    {{ $neighbourhood->title }}</option>
                                                            @endforeach

                                                        </select>
                                                    </div>

                                                    <div class="col-12 mt-3">
                                                        <div id="map" style="width: 100%; height: 400px;"></div>
                                                        <input type="hidden" name="map" id="latLng"
                                                            class="type2-map required  mapinput" />
                                                        <div class="mt-3" id="ControlerContainer"></div>
                                                    </div>

                                                    <input type="hidden" name="latitude" id="input-lat" />
                                                    <input type="hidden" name="longitude" id="input-lng" />


                                                    <button type="submit" class="btn btn-sm btn-success-soft mb-0 mt-4">ثبت</button>

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
            // ایجاد یک شیء نقشه با تنظیمات دلخواه
            const map = new google.maps.Map(document.getElementById('map'), {
                center: {
                    lat: 35.6895,
                    lng: 51.3381
                }, // موقعیت اولیه نقشه (مثلاً تهران)
                zoom: 16,
                mapTypeControl: false,
                streetViewControl: false
            });


            ControlerContainer = document.getElementById('ControlerContainer');
            // ایجاد یک کنترل جستجو
            const input = document.createElement('input');
            input.placeholder = 'جستجوی مکان';
            input.classList.add('search_map_input');
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);


            // ایجاد یک شیء Autocomplete برای جستجو
            const autocomplete = new google.maps.places.Autocomplete(input);

            // ایجاد یک دکمه برای انتخاب موقعیت فعلی کاربر
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
                document.getElementById('input-lat').value = event.latLng.lat();
                document.getElementById('input-lng').value = event.latLng.lng();
            });

            // هنگامی که کاربر دکمه ریست مارکر را فشار می‌دهد، مارکر فعلی حذف می‌شود
            resetMarkerButton.addEventListener('click', () => {
                if (marker) {
                    marker.setMap(null);
                    document.getElementById('latLng').value = '';
                    document.getElementById('input-lat').value = '';
                    document.getElementById('input-lng').value = '';
                }
            });


        }
        // initMap();

        // ('.search_map_input').parent().css("display", "flex")
    </script>
@endsection
