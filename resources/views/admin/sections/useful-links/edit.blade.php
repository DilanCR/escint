
@extends('admin.layouts.master')

@push('css')
    <link rel="stylesheet" href="{{ asset('public/backend/css/fontawesome-iconpicker.css') }}">
    <style>
        .fileholder {
            min-height: 374px !important;
        }
        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 330px !important;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Useful Links")])
@endsection

@section('content')
<div class="custom-card">
    <div class="card-header">
        <h6 class="title">{{ __($page_title) }}</h6>
    </div>

    <div class="card-body">
        <form class="modal-form" method="POST" action="{{ setRoute('admin.useful.links.update') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="target" value="{{ $useful_link->id }}">
            <div class="row mb-10-none mt-3">
                <div class="language-tab">
                    <div class="tab-content" id="nav-tabContent">

                        <div class="tab-pane @if (get_default_language_code() == language_const()::NOT_REMOVABLE) fade show active @endif" id="modal-english" role="tabpanel" aria-labelledby="modal-english-tab">
                            @php
                                $default_lang_code = language_const()::NOT_REMOVABLE;
                            @endphp
                            <div class="form-group">
                                @include('admin.components.form.input',[
                                    'label'     => "Page Name*",
                                    'name'      => "title",
                                    'value'     => old("title", $useful_link->title ?? '')
                                ])
                            </div>
                            <div class="form-group">
                                <label>{{ "Details*" }}</label>
                                <textarea name="details" class="form--control rich-text-editor">
                                    {!! old("details", $useful_link->details ?? '') !!}
                                </textarea>
                            </div>

                        </div> 
                    </div>
                </div>

                <div class="col-xl-12 col-lg-12 form-group d-flex align-items-center justify-content-between mt-4">
                    <a href="{{ setRoute('admin.useful.links.index') }}" class="btn btn--danger modal-close">{{ __("Cancel") }}</a>
                    <button type="submit" class="btn btn--base">{{ __("Update") }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script')
@endpush
