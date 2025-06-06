@extends('user.layouts.master') 
@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("My Escrow")])
@endsection
@push('css')
    <style>
        .text-capitalize{
            text-transform: capitalize;
        }
    </style>
@endpush
@section('content')
    <div class="body-wrapper">
        <div class="table-area mt-20">
            <div class="table-wrapper">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{ __("My Escrow") }}</h4>
                    <div class="dashboard-btn-wrapper">
                        <div class="dashboard-btn">
                            <a href="{{ setRoute('user.my-escrow.add') }}" class="btn--base"><i class="las la-plus me-1"></i> {{ __("Create New Escrow") }}</a>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>{{ __("Escrow Id") }}</th>
                                <th>{{ __("Title") }}</th>
                                <th>{{ __("My Role") }}</th> 
                                <th>{{ __("Amount") }}</th>
                                <th>{{ __("Status") }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($escrowData as $item)
                            <tr>
                                <td>{{ $item->escrow_id}}</td>
                                <td>{{ substr($item->title,0,35)."..." }}</td>
                                <td class="text-capitalize">{{ $item->opposite_role}}</td>
                                <td>{{ get_amount($item->amount, $item->escrow_currency) }}</td>
                                <td><span class="{{ $item->string_status->class}}">{{ $item->string_status->value}}</span></td>
                                <td>
                                    @if ($item->buyer_or_seller_id == auth()->user()->id && $item->status == escrow_const()::APPROVAL_PENDING)
                                    <a href="{{ setRoute('user.escrow-action.paymentApprovalPending', encrypt($item->id))}}" class="btn btn--base bg--warning"><i class="las la-expand"></i></a>
                                    @endif
                                    @if ($item->user_id == auth()->user()->id && $item->opposite_role == "buyer" && $item->status == escrow_const()::PAYMENT_WATTING)
                                    <a href="{{ setRoute('user.my-escrow.payment.crypto.address', $item->escrow_id)}}" class="btn btn--base bg--warning"><i class="las la-expand"></i></a>
                                    @endif
                                    @if ($item->user_id != auth()->user()->id && $item->opposite_role == "buyer" && $item->status == escrow_const()::PAYMENT_WATTING)
                                    <a href="{{ setRoute('user.escrow-action.payment.crypto.address', $item->escrow_id)}}" class="btn btn--base bg--warning"><i class="las la-expand"></i></a>
                                    @endif
                                    {{-- escrow conversation button  --}}
                                    <a href="{{ setRoute('user.escrow-action.escrowConversation', encrypt($item->id))}}" class="btn btn--base chat-btn"><i class="las la-comment"></i>  
                                        @php
                                            $count = 0;
                                        @endphp 
                                        @foreach ($item->conversations as $conversation)
                                            @if ($conversation->seen == 0 && $conversation->sender != auth()->user()->id)
                                                @php
                                                    $count++;
                                                @endphp
                                            @endif
                                        @endforeach
                                        @if ($count > 0) 
                                        <span class="dot"></span>
                                        @endif
                                    </a>
                                    {{-- end escrow conversation button  --}}
                                </td>
                            </tr> 
                            @empty
                            <tr>
                                <td colspan="10"><div class="alert alert-primary" style="margin-top: 37.5px; text-align:center">{{ __("No data found!") }}</div></td>
                            </tr>
                            @endforelse 
                        </tbody>
                    </table>
                    {{ get_paginate($escrowData) }}
                </div>
            </div>
        </div>
    </div>
@endsection
