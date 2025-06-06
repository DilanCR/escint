@extends('admin.layouts.master') 
@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Escrow Category")])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper"> 
            <div class="table-header">
                <h5 class="title">{{ __("Escrow Category") }}</h5>
                <div class="table-btn-area"> 
                    @include('admin.components.search-input',[
                        'name'  => 'escrow_category_search',
                    ])
                    @include('admin.components.link.add-default',[
                        'text'          => __("Add New"),
                        'href'          => "#escrow-category-add",
                        'class'         => "modal-btn", 
                        'permission'    => "admin.escrow.category.store",
                    ])
                </div>
            </div>
            <div class="table-responsive">
               
                @include('admin.components.data-table.escrow-category-table',[
                    'data'  => $escrowCategories
                ])
            </div>
        </div>
        {{ get_paginate($escrowCategories) }}
    </div>
 
    {{-- Escrow Category Add Modal --}}
    @include('admin.components.modals.add-escrow-category')
    
    {{-- Escrow Category edit Modal --}}
    @include('admin.components.modals.edit-escrow-category')

@endsection

@push('script')
    <script>
        $(".delete-modal-button").click(function(){
            var oldData = JSON.parse($(this).parents("tr").attr("data-item"));
            
            var actionRoute =  "{{ setRoute('admin.escrow.category.delete') }}";
            var id      = oldData.id;
            var message     = `Are you sure to delete Escrow Category <strong>${oldData.name}</strong>?`;
             
            openDeleteModal(actionRoute,id,message);
        });
        $(document).ready(function(){
            // Switcher
            switcherAjax("{{ setRoute('admin.escrow.category.status.update') }}");
        })
        itemSearch($("input[name=escrow_category_search]"),$(".escrow-category-search-table"),"{{ setRoute('admin.escrow.category.search') }}",1);
    </script>
@endpush