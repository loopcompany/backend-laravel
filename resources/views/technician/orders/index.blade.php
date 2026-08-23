@extends('technician.layouts.dashboard')

@section('title', 'سفارش‌های من')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="breadcrumb-main">
                <h4 class="text-capitalize breadcrumb-title">سفارش‌های من</h4>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12 mb-25">
            <div class="card border-0">
                <div class="card-header">
                    <h6>فیلتر سفارش‌ها</h6>
                </div>
                <div class="card-body pt-0">
                    <form method="GET" action="{{ route('web.technician.orders') }}" class="row">
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">وضعیت</label>
                            <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                                <option value="">همه</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                                <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>پذیرفته شده</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>در حال انجام</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>تکمیل شده</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="per_page" class="form-label">تعداد در هر صفحه</label>
                            <select name="per_page" id="per_page" class="form-control" onchange="this.form.submit()">
                                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                <option value="20" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    @if(isset($result) && $result['success'])
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0">
                    <div class="card-header">
                        <h6>لیست سفارش‌ها ({{ $result['data']['total'] ?? 0 }} سفارش)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr class="userDatatable-header">
                                        <th><span class="userDatatable-title">شماره سفارش</span></th>
                                        <th><span class="userDatatable-title">دسته‌بندی</span></th>
                                        <th><span class="userDatatable-title">مشتری</span></th>
                                        <th><span class="userDatatable-title">تاریخ</span></th>
                                        <th><span class="userDatatable-title">وضعیت</span></th>
                                        <th><span class="userDatatable-title">مبلغ</span></th>
                                        <th><span class="userDatatable-title text-center">عملیات</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($result['data']['data'] ?? [] as $order)
                                        <tr>
                                            <td>
                                                <div class="userDatatable-content">#{{ $order['id'] ?? '-' }}</div>
                                            </td>
                                            <td>
                                                <div class="userDatatable-content">{{ $order['category_name'] ?? '-' }}</div>
                                            </td>
                                            <td>
                                                <div class="userDatatable-content">{{ $order['user_name'] ?? '-' }}</div>
                                            </td>
                                            <td>
                                                <div class="userDatatable-content">{{ \Morilog\Jalali\Jalalian::forge($order['created_at'])->format('Y/m/d - H:i') }}</div>
                                            </td>
                                            <td>
                                                <div class="userDatatable-content">
                                                    @php
                                                        $statusClass = match($order['status'] ?? '') {
                                                            'pending' => 'warning',
                                                            'accepted' => 'info',
                                                            'in_progress' => 'primary',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                        $statusLabel = match($order['status'] ?? '') {
                                                            'pending' => 'در انتظار',
                                                            'accepted' => 'پذیرفته شده',
                                                            'in_progress' => 'در حال انجام',
                                                            'completed' => 'تکمیل شده',
                                                            'cancelled' => 'لغو شده',
                                                            default => '-'
                                                        };
                                                    @endphp
                                                    <span class="badge badge-{{ $statusClass }}">{{ $statusLabel }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="userDatatable-content">{{ number_format($order['price'] ?? 0) }} تومان</div>
                                            </td>
                                            <td>
                                                <div class="userDatatable-content text-center">
                                                    <a href="#" class="btn btn-sm btn-primary">مشاهده جزئیات</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <div class="dm-empty text-center">
                                                    <div class="dm-empty__text">
                                                        <p class="mb-0">هیچ سفارشی یافت نشد</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        @if(isset($result['data']['data']) && count($result['data']['data']) > 0)
                            <div class="card-footer p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="mb-0">
                                        نمایش {{ $result['data']['from'] ?? 0 }} تا {{ $result['data']['to'] ?? 0 }} از {{ $result['data']['total'] ?? 0 }} سفارش
                                    </p>
                                    
                                    @if(isset($result['data']['links']))
                                        <nav>
                                            <ul class="pagination mb-0">
                                                @foreach($result['data']['links'] as $link)
                                                    <li class="page-item {{ $link['active'] ?? false ? 'active' : '' }} {{ !($link['url'] ?? '') ? 'disabled' : '' }}">
                                                        @if($link['url'] ?? '')
                                                            <a class="page-link" href="{{ $link['url'] }}">{!! $link['label'] ?? '' !!}</a>
                                                        @else
                                                            <span class="page-link">{!! $link['label'] ?? '' !!}</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </nav>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-warning">
                    خطا در بارگذاری سفارش‌ها: {{ $result['message'] ?? 'خطای نامشخص' }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
