@extends('frontend.layouts.master') 
@php
    $defualt = get_default_language_code()??'en';
    $default_lng = 'en';
@endphp
@section('content')  
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Service
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="service-section section--bg pt-80">
        <div class="circle-blur"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 text-center">
                    <div class="section-header">
                        <span class="section-sub-title"><span class="gradient-text">{{ @$service->value->language->$defualt->heading ?? "" }}</span></span>
                        <h2 class="section-title">{{ @$service->value->language->$defualt->sub_heading ?? "" }}</h2>
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

@include('frontend.partials.app-section')

@endsection 
@push("script")
    
@endpush