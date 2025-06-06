@extends('frontend.layouts.master')
@php
    $defualt = get_default_language_code()??'en';
    $default_lng = 'en';
@endphp
@section('content')  
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Contact
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <div class="contact-section ptb-80">
        <div class="container">
            <div class="row justify-content-center mb-30-none">
                <div class="col-xl-8 col-lg-8 mb-30">
                    <div class="contact-form-area">
                        <div class="contact-header">
                            <span class="sub-title gradient-text">{{ $contact->value->language->$defualt->heading ?? "" }}</span>
                            <h2 class="title">{{ $contact->value->language->$defualt->sub_heading ?? "" }}</h2>
                        </div>
                        <form class="contact-form" action="{{ route('contact.store') }}" method="POST">
                            @csrf
                            <div class="row justify-content-center mb-10-none">
                                <div class="col-xl-6 col-lg-6 col-md-12 form-group">
                                    <label>{{ __('Name') }}<span>*</span></label>
                                    <input type="text" name="name" class="form--control" placeholder="{{ __('Enter Name') }}...">
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-12 form-group">
                                    <label>{{ __('Email') }}<span>*</span></label>
                                    <input type="email" name="email" class="form--control" placeholder="{{ __('Enter Email') }}...">
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    <label>{{ __('Message') }}<span>*</span></label>
                                    <textarea class="form--control" name="message" placeholder="{{ __('Write Here') }}..."></textarea>
                                </div>
                                <div class="col-lg-12 form-group">
                                    <button type="submit" class="btn--base mt-20">{{ __('Send Message') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 mb-30">
                    <div class="contact-information">
                        <h3 class="title">{{ $contact->value->language->$defualt->right_title ?? "" }}</h3>
                        <p>{{ $contact->value->language->$defualt->right_details ?? "" }}</p>
                    </div>
                    <div class="contact-widget-box">
                        <div class="contact-widget-item-wrapper">
                            @foreach ($contact->value->items ?? [] as $key => $item)
                            <div class="contact-widget-item">
                                <div class="contact-widget-icon">
                                    <i class="{{ $item->icon ?? "fas fa-check" }}"></i>
                                </div>
                                <div class="contact-widget-content">
                                    <h4 class="title">{{ $item->language->$defualt->title ?? "" }}</h4>
                                    <span class="sub-title"><a href="tel:123123456">{{ $item->language->$defualt->details ?? "" }}</a></span>
                                </div>
                            </div>
                            @endforeach  
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Contact
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

    @include('frontend.partials.app-section')

@endsection 
@push("script")
    
@endpush