@extends('frontend.layouts.master')
@php
    $defualt = get_default_language_code()??'en';
    $default_lng = 'en';
@endphp
@section('content') 
   <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Feature
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="feature-section ptb-80">
        <div class="container">
            <div class="row justify-content-center align-items-center mb-30-none">
                <div class="col-xl-6 col-lg-6 mb-30">
                    <div class="feature-content-wrapper">
                        <div class="feature-content-header">
                            <span class="sub-title gradient-text">{{ @$feature->value->language->$defualt->heading ?? "" }}</span>
                            <h2 class="title">{{ @$feature->value->language->$defualt->sub_heading ?? "" }}</h2>
                        </div>
                        <div class="feature-item-wrapper">
                            @foreach ($feature->value->items ?? [] as $key => $item)
                            <div class="feature-item {{ $loop->index == 0 ? 'active' : '' }}">
                                <div class="feature-icon">
                                    <i class="{{ $item->icon ?? "fas fa-check" }} gradient-text"></i>
                                </div>
                                <div class="feature-content">
                                    <h4 class="title">{{ @$item->language->$defualt->title ?? "" }}</h4>
                                    <p>{{ @$item->language->$defualt->details ?? "" }}</p>
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

    @include('frontend.partials.app-section')

@endsection 
@push("script")
    
@endpush