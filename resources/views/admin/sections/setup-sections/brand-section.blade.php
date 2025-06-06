@php
    $default_lang_code = language_const()::NOT_REMOVABLE;
    $system_default_lang = get_default_language_code();
    $languages_for_js_use = $languages->toJson();
@endphp

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
    ], 'active' => __("Setup Section")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __($page_title) }}</h6>
        </div>
        <div class="card-body"> 
            <div class="table-area mt-15">
                <div class="table-wrapper">
                    <div class="table-header justify-content-end">
                        <div class="table-btn-area">
                            <a href="#brand-add" class="btn--base modal-btn"><i class="fas fa-plus me-1"></i> {{ __("Add Item") }}</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{ __("Item Image") }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody style="background: #838080">
        
                                @forelse ($data->value->items ?? [] as $key => $item)
                                    <tr data-item="{{ json_encode($item) }}">
                                        <td></td>
                                        <td><img src="{{ get_image($item->front_image, 'site-section') }}" alt="" srcset="" width="80"></td>
                                        <td>
                                            <button class="btn btn--base edit-modal-button"><i class="las la-pencil-alt"></i></button>
                                            <button class="btn btn--base btn--danger delete-modal-button" ><i class="las la-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    @include('admin.components.alerts.empty',['colspan' => 4])
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.components.modals.site-section.add-brand-item')

    {{-- Solution Item Edit Modal --}}
    <div id="brand-edit" class="mfp-hide large">
        <div class="modal-data">
            <div class="modal-header px-0">
                <h5 class="modal-title">{{ __("Edit Item") }}</h5>
            </div>
            <div class="modal-form-data">
                <form class="modal-form" method="POST" action="{{ setRoute('admin.setup.sections.section.item.update',$slug) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="target" value="{{ old('target') }}">
                    <div class="row mb-10-none mt-3">
                        <div class="language-tab">
                            <div class="tab-content" id="nav-tabContent">
                                <div class="form-group">
                                    <div class="col-xl-12 col-lg-12 form-group">
                                        @include('admin.components.form.input-file', [
                                            'label' => __('Front Image*'),
                                            'name' => 'front_image_edit',
                                            'class' => 'file-holder',  
                                            'old_files_path'    => files_asset_path("site-section"),
                                            'old_files'         => $data->value->front_image ?? "",
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12 col-lg-12 form-group d-flex align-items-center justify-content-between mt-4">
                            <button type="button" class="btn btn--danger modal-close">{{ __("Cancel") }}</button>
                            <button type="submit" class="btn btn--base">{{ __("Update") }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script src="{{ asset('public/backend/js/fontawesome-iconpicker.js') }}"></script>
    <script>
        // icon picker
        $('.icp-auto').iconpicker();
    </script>
    <script>
        openModalWhenError("brand-add","#brand-add");
        openModalWhenError("brand-edit","#brand-edit");

        var default_language = "{{ $default_lang_code }}";
        var system_default_language = "{{ $system_default_lang }}";
        var languages = "{{ $languages_for_js_use }}";
        languages = JSON.parse(languages.replace(/&quot;/g,'"'));

        $(".edit-modal-button").click(function(){
            var oldData = JSON.parse($(this).parents("tr").attr("data-item"));
            var editModal = $("#brand-edit");

            editModal.find("form").first().find("input[name=target]").val(oldData.id);
            editModal.find("input[name=front_image_edit]").attr("data-preview-name",oldData.front_image);

            fileHolderPreviewReInit("#brand-edit input[name=front_image_edit]");
            openModalBySelector("#brand-edit");

        });

        $(".delete-modal-button").click(function(){
            var oldData = JSON.parse($(this).parents("tr").attr("data-item"));

            var actionRoute =  "{{ setRoute('admin.setup.sections.section.item.delete',$slug) }}";
            var target = oldData.id;

            var message     = `Are you sure to <strong>delete</strong> item?`;

            openDeleteModal(actionRoute,target,message);
        });
    </script>
@endpush
