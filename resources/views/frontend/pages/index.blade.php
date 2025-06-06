@extends('frontend.layouts.master') 
@section('content')  
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start page
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <section class="page-section py-4">
        <div class="container">
            <div class="row justify-content-center pt-4">
                <div class="col-xl-8">
                    <div class="section-header text-center mb-4">
                       <h3>{{ @$page_data->title }}</h3>
                    </div>
                    <div class="section-body">
                        {!! @$page_data->details !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Page
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~--> 
    @include('frontend.partials.app-section')
@endsection 
@push("script")
    
@endpush