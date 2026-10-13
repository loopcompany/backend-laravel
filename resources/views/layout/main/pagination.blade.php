{{-- صفحه‌بندی سایت (RTL، هماهنگ با تم تیره و طلایی لوپ) --}}
@if ($paginator->hasPages())
    <nav class="site-pagination" role="navigation" aria-label="صفحه‌بندی">
        <ul>
            {{-- صفحه قبلی --}}
            @if ($paginator->onFirstPage())
                <li class="disabled" aria-disabled="true">
                    <span class="page-btn page-nav"><i class="fa fa-angle-right"></i> قبلی</span>
                </li>
            @else
                <li>
                    <a class="page-btn page-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="fa fa-angle-right"></i> قبلی
                    </a>
                </li>
            @endif

            {{-- شماره صفحات --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="disabled" aria-disabled="true"><span class="page-btn page-dots">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active" aria-current="page"><span class="page-btn">{{ $page }}</span></li>
                        @else
                            <li><a class="page-btn" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- صفحه بعدی --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a class="page-btn page-nav" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        بعدی <i class="fa fa-angle-left"></i>
                    </a>
                </li>
            @else
                <li class="disabled" aria-disabled="true">
                    <span class="page-btn page-nav">بعدی <i class="fa fa-angle-left"></i></span>
                </li>
            @endif
        </ul>
    </nav>

    <style>
        .site-pagination ul {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .site-pagination .page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-width: 42px;
            height: 42px;
            padding: 0 14px;
            border: 1px solid rgba(220, 168, 44, 0.45);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.04);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            line-height: 1;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .site-pagination .page-btn i {
            font-size: 18px;
        }

        .site-pagination a.page-btn:hover {
            background: #dca82c;
            border-color: #dca82c;
            color: #050b16;
        }

        .site-pagination .active .page-btn {
            background: #dca82c;
            border-color: #dca82c;
            color: #050b16;
        }

        .site-pagination .disabled .page-btn {
            opacity: 0.35;
            cursor: not-allowed;
        }

        .site-pagination .page-dots {
            border-color: transparent;
            background: transparent;
        }

        @media (max-width: 575px) {
            .site-pagination .page-btn {
                min-width: 36px;
                height: 36px;
                padding: 0 10px;
                font-size: 14px;
            }
        }
    </style>
@endif
