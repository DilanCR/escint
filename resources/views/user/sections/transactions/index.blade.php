@extends('user.layouts.master') 
@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Transactions")])
@endsection

@section('content') 
    <div class="body-wrapper">
        <div class="dashboard-list-area mt-20">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ $page_title ?? "" }}</h4>
            </div>
            @include('user.components.wallets.transation-log', compact('transactions'))
        </div>
    </div>

@endsection 