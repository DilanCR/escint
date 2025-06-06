@extends('frontend.layouts.master') 
@php
    $lang = selectedLang();

@endphp 
@section('content') 
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Blog Details
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="blog-section blog-details-section ptb-80">
    <div class="container">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-8 col-lg-7 mb-30">
                <div class="blog-item">
                    <div class="blog-thumb">
                        <img src="{{ get_image(@$blog->image,'blog') }}" alt="blog">
                        <div class="blog-tags">
                            <a href="{{ setRoute('blog.by.category',[$blog->category->id, $blog->category->slug]) }}" class="tags">{{ @$blog->category->name }}</a>
                            <a href="{{ setRoute('blog.by.category',[$blog->category->id, $blog->category->slug]) }}" class="tag-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="10" viewBox="0 0 16 10">
                                    <polygon fill-rule="evenodd" points="104.988 9.2 109.488 9.2 109.488 13.7 107.76 11.972 103.062 16.688 100.074 13.7 95.574 18.2 94.512 17.138 100.074 11.594 103.062 14.582 106.716 10.928" transform="translate(-94 -9)"></polygon>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <div class="blog-content">
                        <div class="blog-info">
                            <div class="author">
                                <span>{{ $blog->admin->firstname ." ". $blog->admin->lastname}}</span>
                            </div>
                            <div class="date">
                                <span>{{showDate($blog->created_at)}}</span>
                            </div>
                        </div>
                        <h3 class="title">{{ @$blog->name->language->$lang->name }}</h3>
                        @php
                            echo @$blog->details->language->$lang->details;
                        @endphp
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-5 mb-30">
                <div class="blog-sidebar">
                    <div class="widget-box mb-30">
                        <h4 class="widget-title">{{ __('Categories') }}</h4>
                        <div class="category-widget-box">
                            <ul class="category-list">
                                @foreach ($categories ?? [] as $cat)
                                @php
                                    $blogCount = App\Models\Blog::where('status',1)->where('category_id',$cat->id)->count();

                                @endphp
                                    @if( $blogCount > 0)
                                    <li><a href="{{ setRoute('blog.by.category',[$cat->id, $cat->slug]) }}"> {{ __($cat->name) }}<span>{{ @$blogCount }}</span></a></li>
                                    @else
                                    <li><a href="javascript:void(0)"> {{ __(@$cat->name) }}<span>{{ @$blogCount }}</span></a></li>
                                    @endif

                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="widget-box mb-30">
                        <h4 class="widget-title">{{ __('Recent Posts') }}</h4>
                        <div class="popular-widget-box">
                            @foreach ($recentPost as $post)
                            <div class="single-popular-item d-flex flex-wrap align-items-center">
                                <div class="popular-item-thumb">
                                    <a href="{{route('blog.details',[$post->id,$post->slug])}}"><img src="{{ get_image(@$post->image,'blog') }}" alt="blog"></a>
                                </div>
                                <div class="popular-item-content">
                                    <span class="date">{{showDate($post->created_at)}}</span>
                                    <h4 class="title"><a href="{{route('blog.details',[$post->id,$post->slug])}}">{{ @$post->name->language->$lang->name }}</a></h4>
                                </div>
                            </div> 
                            @endforeach
                        </div>
                    </div>
                    <div class="widget-box">
                        <h4 class="widget-title">{{ __('Tags') }}</h4>
                        <div class="tag-widget-box">
                            <ul class="tag-list">
                                @foreach ($blog->tags as $tag)
                                <li><a href="javascript:void(0)">{{ @$tag }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Blog Details
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    
    @include('frontend.partials.app-section')

@endsection 
@push("script")
    
@endpush