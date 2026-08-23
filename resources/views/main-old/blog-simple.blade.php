@extends('layout.main.header')

@section('content')
<main>
    <section class="pt-3 pt-xl-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="row g-4">
                        <div class="col-12">
                            <h1>{{ $blog->title }}</h1>
                        </div>
                        
                        <div class="col-12">
                            <img src="{{ asset('storage/'.$blog->image_path) }}" alt="{{ $blog->title }}" class="img-fluid" />
                        </div>
                        
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    {!! $blog->des !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    @if($recent_blogs->count() > 0)
                    <div class="card">
                        <div class="card-body">
                            <h3>آخرین مقالات</h3>
                            @foreach($recent_blogs as $recent)
                                <div class="mb-3">
                                    <h5><a href="{{ route('blog.detail', ['id'=> $recent->id]) }}">{{ $recent->title }}</a></h5>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</main>
@endsection