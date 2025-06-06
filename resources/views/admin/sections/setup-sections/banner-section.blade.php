@php
    $default_lang_code = language_const()::NOT_REMOVABLE;
@endphp
@extends('admin.layouts.master')

@push('css')
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
    ], 'active' => __("Setup Section")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __($page_title) }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.setup.sections.section.update',$slug) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row mb-10-none">
                    <div class="col-xl-12 col-lg-12">
                        <div class="product-tab">
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                    <button class="nav-link @if (get_default_language_code() == language_const()::NOT_REMOVABLE) active @endif" id="english-tab" data-bs-toggle="tab" data-bs-target="#english" type="button" role="tab" aria-controls="english" aria-selected="false">English</button>
                                    @foreach ($languages as $item)
                                        <button class="nav-link @if (get_default_language_code() == $item->code) active @endif" id="{{$item->name}}-tab" data-bs-toggle="tab" data-bs-target="#{{$item->name}}" type="button" role="tab" aria-controls="{{ $item->name }}" aria-selected="true">{{ $item->name }}</button>
                                    @endforeach

                                </div>
                            </nav>
                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane @if (get_default_language_code() == language_const()::NOT_REMOVABLE) fade show active @endif" id="english" role="tabpanel" aria-labelledby="english-tab">
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Left Heading*"),
                                            'name'      => $default_lang_code . "_left_heading",
                                            'value'     => old($default_lang_code . "_left_heading",$data->value->language->$default_lang_code->left_heading ?? "")
                                        ])
                                    </div>
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Left Sub Heading*"),
                                            'name'      => $default_lang_code . "_left_sub_heading",
                                            'value'     => old($default_lang_code . "_left_sub_heading",$data->value->language->$default_lang_code->left_sub_heading ?? "")
                                        ])
                                    </div>
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Left Input One*"),
                                            'name'      => $default_lang_code . "_left_input_one",
                                            'value'     => old($default_lang_code . "_left_input_one",$data->value->language->$default_lang_code->left_input_one ?? "")
                                        ])
                                    </div>
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Left Input Two*"),
                                            'name'      => $default_lang_code . "_left_input_two",
                                            'value'     => old($default_lang_code . "_left_input_two",$data->value->language->$default_lang_code->left_input_two ?? "")
                                        ])
                                    </div>
                                    <div class="form-group">
                                        @include('admin.components.form.textarea',[
                                            'label'     => __("Left Details*"),
                                            'name'      => $default_lang_code . "_left_details",
                                            'value'     => old($default_lang_code . "_left_details",$data->value->language->$default_lang_code->left_details ?? "")
                                        ])
                                    </div>
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Left Button Name*"),
                                            'name'      => $default_lang_code . "_left_button",
                                            'value'     => old($default_lang_code . "_left_button",$data->value->language->$default_lang_code->left_button ?? "")
                                        ])
                                    </div>
                                    {{-- right section --}}
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Right Heading*"),
                                            'name'      => $default_lang_code . "_right_heading",
                                            'value'     => old($default_lang_code . "_right_heading",$data->value->language->$default_lang_code->right_heading ?? "")
                                        ])
                                    </div>
                                    <div class="form-group">
                                        @include('admin.components.form.textarea',[
                                            'label'     => __("Right Details*"),
                                            'name'      => $default_lang_code . "_right_details",
                                            'value'     => old($default_lang_code . "_right_details",$data->value->language->$default_lang_code->right_details ?? "")
                                        ])
                                    </div>
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Right Input One*"),
                                            'name'      => $default_lang_code . "_right_input_one",
                                            'value'     => old($default_lang_code . "_right_input_two",$data->value->language->$default_lang_code->right_input_one ?? "")
                                        ])
                                    </div>
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Right Input Two*"),
                                            'name'      => $default_lang_code . "_right_input_two",
                                            'value'     => old($default_lang_code . "_right_input_two",$data->value->language->$default_lang_code->right_input_two ?? "")
                                        ])
                                    </div>
                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Right Button Name*"),
                                            'name'      => $default_lang_code . "_right_button",
                                            'value'     => old($default_lang_code . "_right_button",$data->value->language->$default_lang_code->right_button ?? "")
                                        ])
                                    </div>
                                </div>

                                @foreach ($languages as $item)
                                    @php
                                        $lang_code = $item->code;
                                    @endphp
                                    <div class="tab-pane @if (get_default_language_code() == $item->code) fade show active @endif" id="{{ $item->name }}" role="tabpanel" aria-labelledby="english-tab">
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Left Heading*"),
                                                'name'      => $lang_code . "_left_heading",
                                                'value'     => old($lang_code . "_button_link",$data->value->language->$lang_code->left_heading ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Left Sub Heading*"),
                                                'name'      => $lang_code . "_left_sub_heading",
                                                'value'     => old($lang_code . "_sub_heading",$data->value->language->$lang_code->left_sub_heading ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Left Input One*"),
                                                'name'      => $lang_code . "_left_input_one",
                                                'value'     => old($lang_code . "_left_input_one",$data->value->language->$lang_code->left_input_one ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Left Input Two*"),
                                                'name'      => $lang_code . "_left_input_two",
                                                'value'     => old($lang_code . "_left_input_two",$data->value->language->$lang_code->left_input_two ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.textarea',[
                                                'label'     => __("Left Details*"),
                                                'name'      => $lang_code . "_left_details",
                                                'value'     => old($lang_code . "_details",$data->value->language->$lang_code->left_details ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Left Button Name*"),
                                                'name'      => $lang_code . "_left_button",
                                                'value'     => old($lang_code . "_left_button",$data->value->language->$lang_code->left_button ?? "")
                                            ])
                                        </div>
                                        {{-- right section --}}
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Right Heading*"),
                                                'name'      => $lang_code . "_right_heading",
                                                'value'     => old($lang_code . "_right_heading",$data->value->language->$lang_code->right_heading ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.textarea',[
                                                'label'     => __("Right Details*"),
                                                'name'      => $lang_code . "_right_details",
                                                'value'     => old($lang_code . "_right_details",$data->value->language->$lang_code->right_details ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Right Input One*"),
                                                'name'      => $lang_code . "_right_input_one",
                                                'value'     => old($lang_code . "_right_input_two",$data->value->language->$lang_code->right_input_one ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Right Input Two*"),
                                                'name'      => $lang_code . "_right_input_two",
                                                'value'     => old($lang_code . "_right_input_two",$data->value->language->$lang_code->right_input_two ?? "")
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'     => __("Right Button Name*"),
                                                'name'      => $lang_code . "_right_button",
                                                'value'     => old($lang_code . "_right_button",$data->value->language->$lang_code->right_button ?? "")
                                            ])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'text'          => "Submit",
                            'permission'    => "admin.setup.sections.section.update"
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    
@endpush