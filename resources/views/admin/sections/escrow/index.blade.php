@extends('admin.layouts.master')

@push('css')
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __('All Escrow Logs'),
    ])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __($page_title) }}</h5>
                <div class="table-btn-area"> 
                    @include('admin.components.search-input',[
                        'name'  => 'escrow_search',
                    ]) 
                </div>
            </div>
            </div>
            <div class="table-responsive">
                @include('admin.components.data-table.escrow-log',[
                    'data'  => $escrows
                ])
            </div>
            {{ get_paginate($escrows) }}
        </div>
    </div>
@endsection

@push('script')
    <script>
        itemSearch($("input[name=escrow_search]"),$(".transaction-search-table"),"{{ setRoute('admin.add.money.search') }}",1);
    </script>
@endpush
