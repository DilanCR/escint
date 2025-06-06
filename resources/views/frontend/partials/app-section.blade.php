
@php
    $defualt     = get_default_language_code()??'en';
    $default_lng = 'en';
    $app_slug    = Illuminate\Support\Str::slug(App\Constants\SiteSectionConst::APP_SECTION);
    $app         = App\Models\Admin\SiteSections::getData( $app_slug)->first();
@endphp
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start App
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="app-section section--bg ptb-80">
        <div class="container">
            <div class="row justify-content-center align-items-center mb-30-none">
                <div class="col-xl-7 mb-30">
                    <div class="app-content">
                        <span class="sub-title gradient-text">{{ @$app->value->language->$defualt->heading ?? "" }}</span>
                        <h2 class="title">{{ @$app->value->language->$defualt->sub_heading ?? "" }}</h2>
                        <p>{{ @$app->value->language->$defualt->details ?? "" }}</p>
                        <div class="app-btn"> 
                            @foreach ($app->value->items ?? [] as $key => $item)
                            <a href="{{ $item->link ?? "#0" }}" class="btn--base {{ $loop->index == 0 ? 'white' : 'white active' }}" target="_blanck">{{ $item->language->$defualt->title ?? "" }} <i class="{{ $item->icon ?? "fas fa-check" }} ms-1"></i></a>
                            @endforeach 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End App
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->