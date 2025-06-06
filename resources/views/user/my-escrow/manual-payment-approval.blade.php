@extends('user.layouts.master') 
@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Escrow Manual Payment")])
@endsection
@push('css')
    <style>
        .text-capitalize{
            text-transform: capitalize;
        }
    </style>
@endpush
@php
    $escrowData = $oldData->data;
@endphp
@section('content')
<div class="body-wrapper">
    <form action="{{ setRoute('user.escrow-action.manual.confirm') }}" class="preview-form" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row mt-20 mb-20-none">
            <div class="col-xl-6 col-lg-6 mb-20">
                <div class="custom-card mt-10">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ $page_title }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="preview-list-wrapper">
                            <div class="row">
                                @foreach ($gateway->input_fields as $item)
                                    @if ($item->type == "select")
                                        <div class="col-lg-12 form-group">
                                            <label for="{{ $item->name }}">{{ $item->label }}
                                                @if($item->required == true)
                                                <span class="text-white">*</span>
                                                @else
                                                <span class="">( {{ __("Optional") }} )</span>
                                                @endif
                                            </label>
                                            <select name="{{ $item->name }}" id="{{ $item->name }}" class="form--control nice-select">
                                                <option selected disabled>Choose One</option>
                                                @foreach ($item->validation->options as $innerItem)
                                                    <option value="{{ $innerItem }}">{{ $innerItem }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @elseif ($item->type == "file")
                                        <div class="col-lg-12 form-group">
                                            <label for="{{ $item->name }}">{{ $item->label }}
                                                @if($item->required == true)
                                                <span class="text-white">*</span>
                                                @else
                                                <span class="">( {{ __("Optional") }} )</span>
                                                @endif
                                            </label>
                                            <input type="{{ $item->type }}" class="form--control" name="{{ $item->name }}" value="{{ old($item->name) }}">
                                        </div>
                                    @elseif ($item->type == "text")
                                        <div class="col-lg-12 form-group">
                                            <label for="{{ $item->name }}">{{ $item->label }}
                                                @if($item->required == true)
                                                <span class="text-white">*</span>
                                                @else
                                                <span class="">( {{ __("Optional") }} )</span>
                                                @endif
                                            </label>
                                            <input type="{{ $item->type }}" class="form--control" placeholder="{{ ucwords(str_replace('_',' ', $item->name)) }}" name="{{ $item->name }}" value="{{ old($item->name) }}">
                                        </div>
                                    @elseif ($item->type == "textarea")
                                        <div class="col-lg-12 form-group">
                                            @include('admin.components.form.textarea',[
                                                'label'     => $item->label,
                                                'name'      => $item->name,
                                                'value'     => old($item->name),
                                            ])
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 mb-20">
                <div class="custom-card mt-10">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ __("Payment Details") }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-battery-half"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Fees & Charge") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--warning">{{ get_amount($escrow->escrowDetails->fee,$escrow->escrow_currency) }}</span>
                                </div>
                            </div> 
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="lab la-get-pocket"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Pay With") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ $escrowData->gateway_currency->name }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="lab la-get-pocket"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Exchange Rate") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>1 {{ $escrow->escrow_currency }} = {{ get_amount($escrowData->escrow->eschangeRate,$escrowData->gateway_currency->currency_code) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span class="last buyerPay">{{ __("You Will Pay") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--info last">{{ get_amount($escrowData->escrow->buyer_amount,$escrowData->gateway_currency->currency_code)}}</span>
                                </div>
                            </div> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn--base mt-20 w-100">{{ __("Confirm & Pay") }}</button>
    </form>
</div>
@endsection