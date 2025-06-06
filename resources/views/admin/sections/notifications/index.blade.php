@extends('admin.layouts.master')

@push('css')
    <style>
        .notiimage{
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ffffff;
            -webkit-box-shadow: 0 2px 10px 0 rgba(0, 0, 0, 0.2);
            box-shadow: 0 2px 10px 0 rgba(0, 0, 0, 0.2);
        }
    </style>
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
        'active' => __($page_title),
    ])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper"> 
            <div class="table-responsive">
                <table class="custom-table transaction-search-table">
                    <thead>
                        <tr> 
                            <th>{{ __("SL")}}</th>
                            <th>{{ __("Image")}}</th>
                            <th>{{ __("Type")}}</th>
                            <th>{{ __("Title")}}</th>
                            <th>{{ __("Message")}}</th>  
                            <th>{{ __("time")}}</th> 
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notifications  as $key => $item)
                            <tr>  
                                <td>{{ $notifications->firstItem()+$loop->index}}</td> 
                                <td>
                                    <img src="{{ $item->message->image }}" alt="user" class="notiimage">
                                </td>
                                <td>{{ @$item->type }}</td>
                                <td>{{ @$item->message->title }}</td>
                                <td>{{ @$item->message->message }}</td>
                                <td>{{ $item->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ get_paginate($notifications) }}
        </div>
    </div>
@endsection

@push('script')
    <script>
        itemSearch($("input[name=search]"),$(".transaction-search-table"),"{{ setRoute('admin.add.money.search') }}",1);
    </script>
@endpush
