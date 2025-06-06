@if (isset($escrow))
    <div class="support-profile-wrapper">
        <div class="support-profile-header escrow-profile-header">
            <div class="escrow-details-btn-wrapper">
                @if ($escrow->status == escrow_const()::ONGOING)
                    @if ($escrow->role == "seller")
                        @if ($escrow->user_id == auth()->user()->id)
                        <button type="button" class="btn--base releaseRequest">{{ __('Release Request') }}</button>
                        @endif 
                        @if ($escrow->user_id != auth()->user()->id)
                        <button type="button" class="btn--base releasePayment">{{ __('Release Payment') }}</button>
                        @endif
                    @endif 
                    @if ($escrow->role == "buyer")
                        @if ($escrow->user_id == auth()->user()->id)
                        <button type="button" class="btn--base releasePayment">{{ __('Release Payment') }}</button>
                        @endif 
                        @if ($escrow->user_id != auth()->user()->id)
                        <button type="button" class="btn--base releaseRequest">{{ __('Release Request') }}</button>
                        @endif
                    @endif 
                    {{-- escrow dispute action --}}
                    @if ($escrow->status == escrow_const()::ONGOING)
                        <button type="button" class="btn--base bg--warning disputePayment">{{ __('Dispute Payment') }}</button>
                    @endif 
                @endif 
            </div>
            <div class="chat-cross-btn">
                <i class="las la-times"></i>
            </div>
        </div>
        <div class="support-profile-body">
            <div class="support-profile-box">
                <h4 class="title">{{ __("Escrow Details") }}</h4>
                <ul class="support-profile-list">
                    <li>{{ __("Title") }} : <span class="mb-3 text-right">{{ $escrow->title }}</span></li>
                    <li>{{ __("My Role") }} : <span class="text-capitalize text-right">{{ $escrow->opposite_role }}</span></li>
                    <li>{{ __("Created By") }} : <span class="text-capitalize text-right">{{ $escrow->user->username }}</span></li>
                    <li>{{ __("Product Type") }} : <span class="text-right">{{ $escrow->escrowCategory->name }}</span></li>
                    <li>{{ __("Total Price") }} : <span class="text-right">{{ get_amount($escrow->amount,$escrow->escrow_currency) }}</span></li>
                    <li>{{ __("Charge Payer") }} : <span class="text-right">{{ $escrow->string_who_will_pay->value }}</span></li>
                    <li>{{ __("Status") }} : <span class="{{ $escrow->string_status->class}} text-right">{{ $escrow->string_status->value}}</span></li>
                    @foreach ($escrow->file ?? [] as $key => $item)
                    <li>{{ __("Attachment") }} : 
                        <span class="text--danger text-right">
                            <a href="{{ setRoute('file.download', ['escrow-temp-file', $item->attachment]) }}">
                                {{ Str::words(json_decode($item->attachment_info)->original_base_name ?? "", 5, '...' . json_decode($item->attachment_info)->extension ?? "" ) }}
                            </a>
                        </span>
                    </li>
                    @endforeach
                    <li>{{ __("Remarks") }} : <span class="mb-3">{{ $escrow->remark }}</span></li>  
                </ul>
            </div> 
            <div class="support-profile-box">
                <h4 class="title">{{ __("Payment Details") }}</h4>
                <ul class="support-profile-list">
                    <li>{{ __("Fees & Charge") }} : <span class="text-right">{{ get_amount($escrow->escrowDetails->fee,$escrow->escrow_currency) }}</span></li>
                    <li>{{ __("Seller Amount") }} : <span class="text-right">{{ get_amount($escrow->escrowDetails->seller_get,$escrow->escrow_currency) }}</span></li>
                    @if ($escrow->payment_type == escrow_const()::GATEWAY)
                    <li>{{ __("Pay with") }} : <span class="text-right">{{ $escrow->paymentGatewayCurrency->name }}</span></li>  
                    <li>{{ __("Exchange Rate") }} : <span class="text-right">{{ "1 ".$escrow->escrow_currency." = ".get_amount($escrow->escrowDetails->gateway_exchange_rate,$escrow->paymentGatewayCurrency->currency_code) }}</span></li>  
                    <li>{{ __("Buyer Paid") }} : <span class="text-right">{{ get_amount($escrow->escrowDetails->buyer_pay,$escrow->paymentGatewayCurrency->currency_code) }}</span></li>  
                    @endif
                    @if ($escrow->payment_type == escrow_const()::MY_WALLET)
                    <li>{{ __("Pay with") }} : <span class="text-right">{{ "Wallet" }}</span></li>  
                    <li>{{ __("Exchange Rate") }} : <span class="text-right">{{ "1 ".$escrow->escrow_currency." = 1 ".$escrow->escrow_currency }}</span></li>  
                    <li>{{ __("Buyer Paid") }} : <span class="text-right">{{ get_amount($escrow->escrowDetails->buyer_pay,$escrow->escrow_currency) }}</span></li>  
                    @endif
                </ul>
            </div> 
        </div>
    </div>
@endif
@push('script')
    <script>
        $(".disputePayment").click(function(){
            var actionRoute =  "{{ setRoute('user.escrow-action.dispute.payment') }}";
            var target      = "{{ $escrow->id }}";
            var message     = `Are you sure to <strong>dispute this payment</strong>?`;
            openAlertModal(actionRoute,target,message,"Confirm","POST");
        });
        $(".releasePayment").click(function(){
            var actionRoute =  "{{ setRoute('user.escrow-action.release.payment') }}";
            var target      = "{{ $escrow->id }}";
            var message     = `Are you sure to <strong>release this payment</strong>?`;
            openAlertModal(actionRoute,target,message,"Confirm","POST");
        });
        $(".releaseRequest").click(function(){
            var actionRoute =  "{{ setRoute('user.escrow-action.release.request') }}";
            var target      = "{{ $escrow->id }}";
            var message     = `Do you want to send a request for <strong>payment</strong>?`;
            openAlertModal(actionRoute,target,message,"Send","POST");
        });
    </script>
@endpush
