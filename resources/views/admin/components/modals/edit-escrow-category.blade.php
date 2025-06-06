@if (admin_permission_by_name("admin.escrow.category.store"))
<div id="escrow-category-edit" class="mfp-hide large">
    <div class="modal-data">
        <div class="modal-header px-0">
            <h5 class="modal-title">{{ __("Edit Escrow Category") }}</h5>
        </div>
        <div class="modal-form-data">
            <form class="modal-form" method="POST" action="{{ setRoute('admin.escrow.category.update') }}" enctype="multipart/form-data">
                @csrf
                @method("PUT")
                <div class="row mb-10-none"> 
                    <div class="col-xl-6 col-lg-6 form-group">
                        @include('admin.components.form.hidden-input',[
                            'name'          => 'target',
                            'value'         => old('target'),
                        ])
                        @include('admin.components.form.input',[
                            'label'         => __("Name").'*',
                            'name'          => 'name',
                            'value'         => old('name')
                        ])
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

    @push("script")
        <script>
            $(document).ready(function(){ 
                openModalWhenError("escrow_category_edit","#escrow-category-edit");

                $(document).on("click",".edit-modal-button",function(){
                    var oldData = JSON.parse($(this).parents("tr").attr("data-item"));
                    
                    var editModal = $("#escrow-category-edit");

                    editModal.find(".invalid-feedback").remove();
                    editModal.find(".form--control").removeClass("is-invalid");

                    editModal.find("form").first().find("input[name=target]").val(oldData.id); 
                    editModal.find("input[name=name]").val(oldData.name);

                    openModalBySelector("#escrow-category-edit");

                });
            });
        </script>
    @endpush
@endif