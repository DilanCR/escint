@extends('frontend.layouts.master') 
@php
    $lang = selectedLang();

@endphp 
@push('css')
<style>
    .justify-content-end {
    justify-content: center !important;
}
</style>
@endpush
@section('content') 
   <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Blog
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="blog-section ptb-80">
        <div class="container">
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
                            <h3 class="title"><a href="{{route('blog.details',[$item->id,$item->slug])}}">{{ @$item->name->language->$lang->name }}</a></h3>
                            <div class="blog-btn">
                                <a href="{{route('blog.details',[$item->id,$item->slug])}}" class="custom-btn">{{ __('Read More') }} <i class="las la-caret-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div> 
                @endforeach
            </div>
                {{ get_paginate($blogs) }}
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Blog
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    
    @include('frontend.partials.app-section')

@endsection 
@push("script")
    
@endpush