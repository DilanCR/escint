@extends('user.layouts.master')

@push('css')
    
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Support Tickets")])
@endsection

@section('content') 
<div class="body-wrapper">
    <div class="table-area mt-20">
        <div class="table-wrapper">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __("Support Tickets") }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <a href="{{ route('user.support.ticket.create') }}" class="btn--base"><i class="las la-plus me-1"></i>{{ __("Add New Ticket") }}</a>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __("Ticket ID") }}</th> 
                            <th>{{ __("Subject") }}</th>
                            <th>{{ __("Message") }}</th>
                            <th>{{ __("Status") }}</th>
                            <th>{{ __("Last Reply") }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($support_tickets as $item)
                            <tr>
                                <td>#{{ $item->token }}</td> 
                                <td><span class="text--info">{{ $item->subject }}</span></td>
                                <td>{{ Str::words($item->desc, 10, '...') }}</td>
                                <td>
                                    <span class="{{ $item->stringStatus->class }}">{{ $item->stringStatus->value }}</span>
                                </td>
                                <td>{{ $item->created_at->format("Y-m-d H:i A") }}</td>
                                <td>
                                    <a href="{{ route('user.support.ticket.conversation',encrypt($item->id)) }}" class="btn btn--base"><i class="las la-comment"></i></a>
                                </td>
                            </tr>
                        @empty
                        <tr>
                            <td colspan="10"><div class="alert alert-primary" style="margin-top: 37.5px; text-align:center">{{ __("No data found!") }}</div></td>
                        </tr>
                        @endforelse
                        
                    </tbody>
                </table>
            </div>
        </div>
        {{ get_paginate($support_tickets) }}
    </div>
</div>
@endsection

@push('script')
    <script>

    </script>
@endpush