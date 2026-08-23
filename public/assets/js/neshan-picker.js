window.neshanMapPicker = function (config) {

    return {
        map: null,
        marker: null,
        selectedLat: config.latitude && config.longitude && !(config.latitude == 35.6895 && config.longitude == 51.3381) ? config.latitude : null,
        selectedLng: config.latitude && config.longitude && !(config.latitude == 35.6895 && config.longitude == 51.3381) ? config.longitude : null,
        statePath: config.statePath,
        initialized: false,
        canPlaceMarker: true,

        // Search state
        searchStatusText: '',
        showResults: false,
        searchItems: [],
        lastController: null,
        SEARCH_API_KEY: 'service.1acf65643b524ab9906b1f22bbec6deb',

        initMap() {
            if (this.initialized) return;

            const initLat = parseFloat(config.latitude) || 35.6895;
            const initLng = parseFloat(config.longitude) || 51.3381;

            this.map = new L.Map(this.$refs.mapContainer, {
                key: 'web.a7d38181a0094e0092a578bcc81b7641',
                maptype: 'neshan',
                poi: true,
                traffic: true,
                center: [initLat, initLng],
                zoom: 14,
            });
            setTimeout(() => {
                this.map.invalidateSize();
            }, 300);

            window.addEventListener('resize', () => {
                this.map.invalidateSize();
            });

            document.addEventListener('modal-open', () => {
                this.map.invalidateSize();
            });

            // اگر مختصات معتبر ذخیره شده بود، مارکر نمایش بده
            if (config.latitude && config.longitude && !(config.latitude == 35.6895 && config.longitude == 51.3381)) {
                this.setSingleMarker(initLat, initLng, false);
                this.canPlaceMarker = false;
            }

            // کلیک روی نقشه
            this.map.on('click', (e) => {
                if (!this.canPlaceMarker) {
                    alert('⚠️ یک موقعیت از قبل انتخاب شده است.\nبرای انتخاب موقعیت جدید، از دکمه "انتخاب مجدد موقعیت" استفاده کنید.');
                    return;
                }
                this.setSingleMarker(e.latlng.lat, e.latlng.lng, true);
            });

            // کلیک بیرون از search box
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.neshan-search-box')) {
                    this.clearResults();
                }
            });

            this.initialized = true;
        },

        setSingleMarker(lat, lng, updateState = true) {
            if (this.marker) {
                this.map.removeLayer(this.marker);
            }
            this.marker = L.marker([lat, lng]).addTo(this.map);

            this.selectedLat = parseFloat(lat).toFixed(6);
            this.selectedLng = parseFloat(lng).toFixed(6);
            this.canPlaceMarker = false;

            if (updateState) {
                this.updateFormState(lat, lng);
            }
        },

        updateFormState(lat, lng) {
            const latVal = parseFloat(lat).toFixed(6);
            const lngVal = parseFloat(lng).toFixed(6);

            // ست کردن مقدار به صورت JSON
            this.$wire.call('$set', this.statePath, {
                latitude: latVal,
                longitude: lngVal
            }, false);
        },

        resetMarker() {
            if (this.marker) {
                this.map.removeLayer(this.marker);
                this.marker = null;
            }
            this.selectedLat = null;
            this.selectedLng = null;
            this.canPlaceMarker = true;

            // ست کردن null
            this.$wire.call('$set', this.statePath, null, false);
        },

        // ---- Search ----
        async doSearch(term) {
            term = (term || '').trim();
            if (term.length < 2) {
                this.clearResults();
                this.searchStatusText = '';
                return;
            }

            const center = this.map.getCenter();
            const lat = center.lat;
            const lng = center.lng;

            if (this.lastController) this.lastController.abort();
            this.lastController = new AbortController();

            this.searchStatusText = 'در حال جستجو...';
            this.showResults = false;

            const url = `https://api.neshan.org/v1/search?term=${encodeURIComponent(term)}&lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;

            try {
                const res = await fetch(url, {
                    method: 'GET',
                    headers: { 'Api-Key': this.SEARCH_API_KEY },
                    signal: this.lastController.signal
                });

                if (!res.ok) {
                    throw new Error(this.mapNeshanError(res.status));
                }

                const data = await res.json();
                const items = Array.isArray(data?.items) ? data.items : [];
                this.searchItems = items.slice(0, 20);

                if (!items.length) {
                    this.searchStatusText = 'نتیجه‌ای پیدا نشد.';
                    this.showResults = false;
                    return;
                }

                this.searchStatusText = `تعداد نتایج: ${data.count ?? items.length}`;
                this.showResults = true;

            } catch (err) {
                if (err?.name === 'AbortError') return;
                console.error(err);
                this.searchStatusText = err?.message || 'خطای ناشناخته';
                this.showResults = false;
            }
        },

        selectResult(item) {
            const lat = item?.location?.y;
            const lng = item?.location?.x;

            if (typeof lat !== 'number' || typeof lng !== 'number') {
                this.searchStatusText = 'مختصات این نتیجه معتبر نیست.';
                return;
            }

            this.map.setView([lat, lng], 16);

            // برای جستجو اجازه ایجاد پین بده
            this.canPlaceMarker = true;
            this.setSingleMarker(lat, lng, true);

            this.$refs.searchInput.value = item.title || '';
            this.clearResults();
            this.searchStatusText = '';
        },

        clearResults() {
            this.showResults = false;
            this.searchItems = [];
        },

        mapNeshanError(status) {
            switch (status) {
                case 400: return 'پارامترهای ورودی نامعتبر است (400).';
                case 470: return 'مختصات جغرافیایی ارسالی معتبر نیست (470).';
                case 480: return 'Api-Key نامعتبر است یا ارسال نشده (480).';
                case 481: return 'سقف مجاز استفاده (Limit) تمام شده (481).';
                case 482: return 'تعداد درخواست‌ها در دقیقه بیش از حد مجاز است (482).';
                case 483: return 'نوع Api-Key با سرویس Search همخوانی ندارد (483).';
                case 484: return 'این کلید با تنظیمات WhiteList مجاز نیست (484).';
                case 485: return 'سرویس Search برای این Api-Key فعال نیست (485).';
                case 500: return 'خطای ناشناخته سمت سرور (500).';
                default: return `خطا در جستجو (HTTP ${status}).`;
            }
        }
    }
}