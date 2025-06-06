@extends('frontend.layouts.master') 
@php
    $defualt = get_default_language_code()??'en';
    $default_lng = 'en';
@endphp
@section('content')  
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start About
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="about-section ptb-80">
        <div class="circle-blur"></div>
        <div class="container">
            <div class="row justify-content-center align-items-center mb-30-none">
                <div class="col-xl-6 col-lg-6 mb-30">
                    <div class="about-thumb">
                        <img src="{{ get_image($about->value->image ?? "","site-section") }}" alt="banner">
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 mb-30">
                    <div class="about-content">
                        <span class="sub-title gradient-text">{{ $about->value->language->$defualt->heading ?? "" }}</span>
                        <h2 class="title">{{ $about->value->language->$defualt->sub_heading ?? "" }}</h2>
                        <p>{{ $about->value->language->$defualt->details ?? "" }}</p>
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

    @include('frontend.partials.app-section')

@endsection 
@push("script")
    
@endpush