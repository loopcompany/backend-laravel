<section>
    <div class="container">

        <div class="row mb-4">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="fs-3">{{ $field->title }}
                <br>
    <small>{{ $field->des }}</small></h2>
            </div>
        </div>


        <ul class="nav nav-pills nav-pills-bg-soft justify-content-sm-center mb-4 px-3" id="course-pills-tab"
            role="tablist">
            @foreach ($field->field_details as $item)
                <li class="nav-item me-2 me-sm-5">
                    <button class="nav-link mb-2 mb-md-0 @if ($loop->iteration == 1) active @endif"
                        id="course-pills-tab-{{ $loop->iteration }}" data-bs-toggle="pill"
                        data-bs-target="#course-pills-tabs-{{ $loop->iteration }}" type="button" role="tab"
                        aria-controls="course-pills-tabs-{{ $loop->iteration }}"
                        aria-selected="false">{{ $item->title }} 
                        <br>
                        <small>{{ $field->des }}</small>
                        </button>
                </li>
            @endforeach
        </ul>


        <div class="tab-content" id="course-pills-tabContent">
            @foreach ($field->field_details as $item)
                <div class="tab-pane fade @if ($loop->iteration == 1) show active @endif"
                    id="course-pills-tabs-{{ $loop->iteration }}" role="tabpanel"
                    aria-labelledby="course-pills-tab-{{ $loop->iteration }}">
                    <div class="row g-4 mt-4 px-3" style="text-align: justify">
                         {!! $item->des !!} 

                        @if ($item->field_charts->count())
                            @foreach ($item->field_charts as $chart)
                                <div class="table-responsive border-0">
                                    <h5
                                        style=" background: #faa824; color: white; padding: 10px; text-align: center; border-radius: 10px 10px 0 0; margin:0">
                                        {{ $chart->title }}</h5>
                                    <table class="table table-dark-gray align-middle p-4 mb-0 table-hover">
                                        <thead>
                                            <tr>
                                                @for ($i = 1; $i <= $chart->columns_count; $i++)
                                                    <th scope="col" style="background: #e4e4e4; color: #970098;">
                                                        @switch($i)
                                                            @case(1)
                                                                {{ $chart->first_column }}
                                                            @break

                                                            @case(2)
                                                                {{ $chart->second_column }}
                                                            @break

                                                            @case(3)
                                                                {{ $chart->third_column }}
                                                            @break
                                                        @endswitch
                                                    </th>
                                                @endfor

                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($chart->chart_options as $option)
                                                <tr>
                                                    @for ($i = 1; $i <= $chart->columns_count; $i++)
                                                        <td class="border-b">
                                                            @switch($i)
                                                                @case(1)
                                                                    {{ $option->first }}
                                                                @break

                                                                @case(2)
                                                                    {{ $option->second }}
                                                                @break

                                                                @case(3)
                                                                    {{ $option->third }}
                                                                @break
                                                            @endswitch
                                                        </td>
                                                    @endfor
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        @endif

                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>





<style>
    .title {
        background: #fd9714;
        color: white;
        width: 100%;
        padding: 10px;
        border-radius: 10px;
        margin: 10px 0;
    }

    .pre {
        white-space: pre-line;
        font-family: 'IRANSans';
    }
</style>
