@extends('frontend.layouts.master')
@php
    $defualt = get_default_language_code()??'en';
    $default_lng = 'en';
@endphp
@section('content') 
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Banner
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="banner-section bg_img" data-background="{{ asset('public/frontend/images/element/banner-bg.png') }}">
        <div class="container">
            <div class="row align-items-center mb-30-none">
                <div class="col-xxl-6 col-xl-5 col-lg-12 mb-30">
                    <div class="banner-content">
                        <span class="sub-title">{{ @$banner->value->language->$defualt->left_heading ?? ""}}</span>
                        <h1 class="title cd-headline clip">
                            {{ @$banner->value->language->$defualt->left_sub_heading ?? ""}}
                            <span class="cd-words-wrapper">
                                <b class="is-visible">{{ @$banner->value->language->$defualt->left_input_one ?? ""}}</b>
                                <b>{{ @$banner->value->language->$defualt->left_input_two ?? ""}}</b>
                            </span>
                        </h1>
                        <p>{{ @$banner->value->language->$defualt->left_details ?? ""}}</p>
                        <div class="banner-btn">
                            <a href="{{ setRoute('user.register') }}" class="btn--base">{{ @$banner->value->language->$defualt->left_button ?? ""}}</a>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-xl-7 col-lg-12 mb-30">
                    <div class="banner-form-wrapper">
                        <div class="ribbon-top-right">{{ @$banner->value->language->$defualt->right_input_two ?? "Popular"}}</div>
                        <h2 class="title">{{ @$banner->value->language->$defualt->right_heading ?? ""}}</h2>
                        <p>{{ @$banner->value->language->$defualt->right_details ?? ""}}</p>
                        <form action="{{ route('user.my-escrow.add')}}" method="get" class="banner-form">
                            <div class="banner-form-group">
                                <div class="left-field">
                                    <div class="field-input">
                                        <div class="field-preffix">
                                            <div class="field-preffix-wrapper">
                                                <span class="field-preffix-label">{{ __("I'm") }}</span>
                                            </div>
                                        </div>
                                        <div class="field-select">
                                            <select class="form--control nice-select" name="role">
                                                <option value="seller">{{ __('Selling') }}</option>
                                                <option value="buyer">{{ __('Buying') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="right-field">
                                    <div class="field-input">
                                        <input type="text" class="banner-input" data-target="field-focusable" id="field-calculator-search" name="title" placeholder="{{ @$banner->value->language->$defualt->right_input_one ?? ""}}" data-component="calculator-price" aria-describedby=" error-price" data-e2e-target="calculator-price-input" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="banner-form-group">
                                <div class="field-price">
                                    <div class="field-input">
                                        <div class="field-preffix">
                                            <div class="field-preffix-wrapper">
                                                <span class="field-preffix-label currency-symbol">{{ __('for') }} $</span>
                                            </div>
                                        </div>
                                        <input type="text" class="defaultInput" data-target="field-focusable" id="amount" value="1000" name="amount" step="10" min="0" data-component="calculator-price" aria-describedby=" error-price" data-e2e-target="calculator-price-input" autocomplete="off">
                                    </div>
                                </div>
                                <div class="select-field">
                                    <div class="field-select">
                                        <select class="form--control nice-select" name="escrow_currency">
                                            @foreach ($currencies ?? [] as $item)
                                            <option value="{{ $item->code }}" data-symbol="{{ $item->symbol }}">{{ $item->code }}</option>
                                            @endforeach
                                             
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn--base mt-10">{{ @$banner->value->language->$defualt->right_button ?? "Get Started Now"}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Banner
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Brand
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="brand-section pt-80">
        <div class="container">
            <div class="brand-slider">
                <div class="swiper-wrapper">
                    @foreach ($brand->value->items ?? [] as $key => $item)
                    <div class="swiper-slide">
                        <div class="brand-item">
                            <a href="#0" class="brand-thumb">
                                <div class="front">
                                    <img src="{{ get_image($item->front_image, "site-section") }}" alt="brand">
                                </div>
                                <div class="back">
                                    <img src="{{ get_image($item->front_image, "site-section") }}" alt="brand">
                                </div>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Brand
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start About
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="about-section ptb-80">
        <div class="circle-blur"></div>
        <div class="container">
            <div class="row justify-content-center align-items-center mb-30-none">
                <div class="col-xl-6 col-lg-6 mb-30">
                    <div class="about-thumb">
                        <img src="{{ get_image($about->value->image,"site-section") }}" alt="element">
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 mb-30">
                    <div class="about-content">
                        <span class="sub-title gradient-text">{{ @$about->value->language->$defualt->heading }}</span>
                        <h2 class="title">{{ @$about->value->language->$defualt->sub_heading }}</h2>
                        <p>{{ @$about->value->language->$defualt->details }}</p>
                        <ul class="about-list">
                            @foreach ($about->value->items ?? [] as $key => $item)
                            <li><i class="{{ $item->icon ?? "fas fa-check" }}"></i> {{ $item->language->$defualt->title ?? "" }}</li>
                            @endforeach 
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End About
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Service
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="service-section section--bg ptb-80">
        <div class="circle-blur"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 text-center">
                    <div class="section-header">
                        <span class="section-sub-title"><span class="gradient-text">{{ @$service->value->language->$defualt->heading}}</span></span>
                        <h2 class="section-title">{{ @$service->value->language->$defualt->sub_heading }}</h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center mb-60-none">
                @foreach ($service->value->items ?? [] as $key => $item)
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 mb-60">
                    <div class="service-item">
                        <div class="service-icon gradient-text">
                            <i class="{{ $item->icon ?? "fas fa-check" }}"></i>
                        </div>
                        <div class="service-content">
                            <h4 class="title">{{ @$item->language->$defualt->title ?? "" }}</h4>
                            <p>{{ @$item->language->$defualt->details ?? "" }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Service
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Feature
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="feature-section pt-80">
        <div class="container">
            <div class="row justify-content-center align-items-center mb-30-none">
                <div class="col-xl-6 col-lg-6 mb-30">
                    <div class="feature-content-wrapper">
                        <div class="feature-content-header">
                            <span class="sub-title gradient-text">{{ $feature->value->language->$defualt->heading ?? "" }}</span>
                            <h2 class="title">{{ $feature->value->language->$defualt->sub_heading ?? "" }}</h2>
                        </div>
                        <div class="feature-item-wrapper">
                            @foreach ($feature->value->items ?? [] as $key => $item)
                            <div class="feature-item {{ $loop->index == 0 ? 'active' : '' }}">
                                <div class="feature-icon">
                                    <i class="{{ $item->icon ?? "fas fa-check" }} gradient-text"></i>
                                </div>
                                <div class="feature-content">
                                    <h4 class="title">{{ $item->language->$defualt->title ?? "" }}</h4>
                                    <p>{{ $item->language->$defualt->details ?? "" }}</p>
                                </div>
                            </div> 
                            @endforeach 
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 mb-30">
                    <div class="feature-thumb">
                        <img src="{{ get_image($feature->value->image ?? "","site-section") }}" alt="element">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Feature
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Testimonial
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="testimonial-section pt-80">
        <div class="circle-blur"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-7 text-center">
                    <div class="section-header">
                        <span class="section-sub-title"><span class="gradient-text">{{ $testimonial->value->language->$defualt->heading ?? "" }}</span></span>
                        <h2 class="section-title">{{ $testimonial->value->language->$defualt->sub_heading ?? "" }}</h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-12">
                    <div class="testimonial-area">
                        <div class="testimonial-slider">
                            <div class="swiper-wrapper">
                                @foreach ($testimonial->value->items ?? [] as $key => $item)
                                <div class="swiper-slide">
                                    <div class="testimonial-item">
                                        <div class="testimonial-content">
                                            <div class="testimonial-ratings">
                                                @for ($i = 0; $i < $item->icon_show; $i++)
                                                <i class="fas fa-star"></i>
                                                @endfor 
                                            </div>
                                            <p>{{ @$item->language->$defualt->details ?? "" }}</p>
                                            <div class="testimonial-user-wrapper">
                                                <div class="testimonial-user-thumb">
                                                    <img src="{{ get_image($item->user_image ?? "","site-section") }}" alt="user">
                                                </div>
                                                <div class="testimonial-user-content">
                                                    <h4 class="title">{{ $item->user_name ?? "" }}</h4>
                                                    <span class="sub-title">{{ $item->user_type ?? "" }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div> 
                                </div> 
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Testimonial
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    @php 
        $blogPage = App\Models\Admin\SetupPage::where(['type' => 'setup-page', 'status' => true])->Where('slug',"blogs")->first();
    @endphp
    @if ($blogPage != null)
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Blog
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="blog-section ptb-80">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-7 text-center">
                    <div class="section-header">
                        <span class="section-sub-title"><span class="gradient-text">{{ __('Blog') }}</span></span>
                        <h2 class="section-title">{{ __('See Our Recent Blog') }}</h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center mb-30-none">
                @foreach ($blogs ?? [] as $item)
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 mb-30">
                    <div class="blog-item">
                        <div class="blog-thumb">
                            <img src="{{ get_image($item->image,'blog') }}" alt="blog">
                            <div class="blog-tags">
                                <a href="{{ setRoute('blog.by.category',[$item->category->id, $item->category->slug]) }}" class="tags">{{ $item->category->name }}</a>
                                <a href="{{ setRoute('blog.by.category',[$item->category->id, $item->category->slug]) }}" class="tag-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="10" viewBox="0 0 16 10">
                                        <polygon fill-rule="evenodd" points="104.988 9.2 109.488 9.2 109.488 13.7 107.76 11.972 103.062 16.688 100.074 13.7 95.574 18.2 94.512 17.138 100.074 11.594 103.062 14.582 106.716 10.928" transform="translate(-94 -9)"></polygon>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div class="blog-content">
                            <div class="blog-info">
                                <div class="author">
                                    <span>{{ $item->admin->firstname ." ". $item->admin->lastname}}</span>
                                </div>
                                <div class="date">
                                    <span>{{showDate($item->created_at)}}</span>
                                </div>
                            </div>
                            <h3 class="title"><a href="{{route('blog.details',[$item->id,$item->slug])}}">{{ @$item->name->language->$defualt->name }}</a></h3>
                            <div class="blog-btn">
                                <a href="{{route('blog.details',[$item->id,$item->slug])}}" class="custom-btn">{{ __('Read More') }} <i class="las la-caret-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div> 
                @endforeach
            </div>
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Blog
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
        
    @endif
    @include('frontend.partials.app-section')
@endsection
@push('script')
    <script>
        $(document).ready(function(){
            currencyCheck();
        });
        $('select[name=escrow_currency]').on('change',function(){ 
            currencyCheck();
        });
        function currencyCheck(){
            var currencySymbol = $("select[name=escrow_currency] :selected").attr("data-symbol"); 
            $('.currency-symbol').text({{ __('for') }}+ currencySymbol);
        }

        const numberField = document.getElementById('amount'); 
        numberField.addEventListener('input', function (e) {
            const input = e.target.value;
            // Use a regular expression to match only numeric characters
            const numericInput = input.replace(/[^0-9]/g, '');
            // Set the input field value to the numeric input
            e.target.value = numericInput;
        });
    </script>
@endpush
