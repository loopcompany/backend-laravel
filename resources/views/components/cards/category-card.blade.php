<div class="col-md-6 col-lg-6 col-xxl-3">
        <div class="card p-2 shadow h-100">
            <div class="rounded-top overflow-hidden position-relative">
                <a href="{{ route('web.category.show', ['id' => $category->id, 'slug' => $category->slug ?? '']) }}">

                    {{-- @dd($category->category_labels, $label); --}}
                        @if ($label && $label->where('type', 1)->first())
                            <div class="position-absolute" style="bottom:7px; left:5px">
                            <span class="badge" style="background: {{ $label->where('type', 1)->first()->label->color }}; color:  {{ $label->where('type', 1)->first()->label->color_font }};">
                                {{ $label->where('type', 1)->first()->label->title }}
                            </span>
                            </div>
                        @endif


                        @if($avgRate > 0)
                            <div class="position-absolute" style="top:7px; right:5px; z-index:2;">
                                <span class="badge bg-warning text-dark" title="میانگین امتیاز">
                                    <i class="fas fa-star text-white"></i>
                                    {{ $avgRate }}
                                </span>
                            </div>
                        @endif
                        <img src="{{ asset('storage/' . $category->image_path) }}"
                            class="card-img-top" alt="{{ $category->title }}"
                            style="height:150px;object-fit:cover;">
                </a>
            </div>
            <div class="card-body px-2">
            
                <h6 class="card-title fw-normal">
                        <a href="{{ route('web.category.show', ['id' => $category->id, 'slug' => $category->slug ?? '']) }}">
                            {{ $category->title }}
                        </a>
                </h6>

                @if ($label && $label->where('type', 0)->first())
                    <div class="position-absolute" style="bottom:7px; left:5px">
                    <span class="badge" style="background: {{ $label->where('type', 0)->first()->label->color }}; color: {{ $label->where('type', 0)->first()->label->color_font }};">
                        {{ $label->where('type', 0)->first()->label->title }}
                    </span>
                    </div>
                @endif
                


                @if($orderscount > 100)
                    <div class="d-flex justify-content-between align-items-center mb-0">
                        <a href="{{ route('web.category.show', ['id' => $category->id, 'slug' => $category->slug ?? '']) }}"
                            class="badge bg-info bg-opacity-10 text-info me-2">
                            <i class="fas fa-circle small fw-bold mx-2"></i>
                            تعداد سفارشات ({{ $orderscount }})
                        </a>
                    </div>
                @endif
            </div>
        </div>
</div>