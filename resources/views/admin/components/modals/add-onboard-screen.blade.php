@if (admin_permission_by_name("admin.app.settings.onboard.screen.store"))
    <div id="onboard-screen-add" class="mfp-hide large">
        <div class="modal-data">
            <div class="modal-header">
                <h5 class="modal-title">{{ __("Add New Screen") }}</h5>
            </div>
            <div class="modal-form-data">
                <form class="modal-form" method="POST" action="{{ setRoute('admin.app.settings.onboard.screen.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row mb-10-none">
                        <div class="card-body">
                            <div class="row mb-10-none">
                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 form-group">
                                    @include('admin.components.form.input-file',[
                                        'label'             => __("Image").': <span class="text--danger">(414*896)</span>',
                                        'class'             => "file-holder",
                                        'name'              => "image",
                                    ])
                                </div>
                                <div class="col-xl-8 col-lg-8 col-md-6">

                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Title"),
                                            'name'      => "title",
                                            'attribute' => "data-limit=120",
                                            'value'     => old('title'),
                                        ])
                                    </div>

                                    <div class="form-group">
                                        @include('admin.components.form.input',[
                                            'label'     => __("Sub Title"),
                                            'name'      => "sub_title",
                                            'attribute' => "data-limit=255",
                                            'value'     => old('sub_title'),
                                        ])
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group d-flex align-items-center justify-content-between mt-4">
                            <button type="button" class="btn btn--danger modal-close">{{ __("Cancel") }}</button>
                            <button type="submit" class="btn btn--base">{{ __("Add") }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            openModalWhenError("onboard-screen-add","#onboard-screen-add");
        </script>
    @endpush
@endif