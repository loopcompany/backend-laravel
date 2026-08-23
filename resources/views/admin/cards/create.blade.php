<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ایجاد کارت ویزیت</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-slate-100 text-slate-900">
    <style>
        @font-face {
            font-family: 'LoopCardFont';
            src: url('{{ asset('assets/fonts/vazir/UI-Farsi-Digits-Non-Latin/fonts/webfonts/Vazirmatn-UI-FD-NL-Regular.woff2') }}') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'VazirFont';
            src: url('{{ asset('/assets/fonts/vazir/Vazir.woff2') }}') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'IRANSansFont';
            src: url('{{ asset('/assets/fonts/IRANSans/IRANSansWeb(FaNum).woff') }}') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
    </style>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between gap-3">
            <div style="font-family: 'LoopCardFont', sans-serif;">
                <p class="text-sm font-medium uppercase tracking-[0.3em] text-slate-500">کارت ویزیت لوپ</p>
                <h1 class="mt-1 text-3xl font-bold text-slate-900">ایجاد کارت</h1>
                <p class="mt-2 text-sm text-slate-600" >کارت ویزیت جدید
                    خود را ایجاد کنید و در قسمت ادیتور آن را ویرایش کنید</p>
            </div>
            <a href="{{ url()->previous() }}"
                class="rounded-xl bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800" style="font-family: 'LoopCardFont', sans-serif;">بازگشت</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800" style="font-family: 'LoopCardFont', sans-serif;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800" style="font-family: 'LoopCardFont', sans-serif;">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.cards.store') }}" method="POST" enctype="multipart/form-data"
            class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" style="font-family: 'LoopCardFont', sans-serif;">
            @csrf

            <label class="block">
                <span class="mb-2 block text-sm font-medium text-slate-700">عنوان <span style="color: red">*</span></span>
                <input type="text" name="title" value="{{ old('title') }}"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-slate-900" style="font-family: 'LoopCardFont', sans-serif;"
                    required>
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-medium text-slate-700">اسلاگ <span style="color: red">*</span></span>
                <input type="text" name="slug" value="{{ old('slug') }}"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-slate-900" style="font-family: 'LoopCardFont', sans-serif;"
                    required>
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-medium text-slate-700">توضیحات <span style="color: red">*</span></span>
                <textarea name="des" rows="4"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-slate-900" style="font-family: 'LoopCardFont', sans-serif;">{{ old('des') }}</textarea>
            </label>

            <div class="grid gap-5 md:grid-cols-2" style="font-family: 'LoopCardFont', sans-serif;">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700" >لوگو <span style="color: red">*</span></span>
                    <input type="file" name="logo" accept="image/*"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm" required>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">تصویر هدر <span style="color: red">*</span></span>
                    <input type="file" name="image_background" accept="image/*"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm" required>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ url()->previous() }}"
                    class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">لغو</a>
                <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white">ایجاد کارت و رفتن به ادیتور</button>
            </div>
        </form>
    </div>
</body>

</html>
