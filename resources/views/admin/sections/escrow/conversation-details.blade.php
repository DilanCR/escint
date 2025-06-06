 
    <div class="support-profile-wrapper">
        <div class="support-profile-header escrow-profile-header">
            <div class="escrow-details-btn-wrapper">
                    <button type="button" class="btn--base releaseToBuyer">Release to Buyer</button>
                    <button type="button" class="btn--base releaseToSeller">Release to Seller</button>
            </div>
            <div class="chat-cross-btn">
                <i class="las la-times"></i>
            </div>
        </div>
        <div class="support-profile-body">
            <div class="support-profile-box">
                <h5 class="title">{{ __("Escrow Details") }}</h5>
                <ul class="support-profile-list">
                    <li>{{ __("Title") }} : <span>{{ $escrows->title }}</span></li> 
                    <li>{{ __("Role") }} : <span class="text-capitalize">{{ $escrows->role }}</span></li>
                    <li>{{ __("Created By") }} : <span class="text-capitalize">{{ $escrows->user->username }}</span></li>
                    <li>{{ __("Product Type") }} : <span>{{ $escrows->escrowCategory->name }}</span></li>
                    <li>{{ __("Total Price") }} : <span>{{ get_amount($escrows->amount,$escrows->escrow_currency) }}</span></li>
                    <li>{{ __("Charge Payer") }} : <span>{{ $escrows->string_who_will_pay->value }}</span></li>
                    <li>{{ __("Status") }} : <span class="{{ $escrows->string_status->class}}">{{ $escrows->string_status->value}}</span></li>
                    @foreach ($escrows->file ?? [] as $key => $item)
                    <li>{{ __("Attachments") }} - {{ $key + 1 }} : 
                        <span class="text--danger">
                            <a href="{{ files_asset_path('escrow-temp-file') . "/" . $item->attachment }}" target="_blanck">
                                {{ Str::words(json_decode($item->attachment_info)->original_base_name ?? "", 5, '...' . json_decode($item->attachment_info)->extension ?? "" ) }}
                            </a>
                        </span>
                    </li>
                    @endforeach
                    <li>{{ __("Remarks") }} : <span class="mb-3">{{ $escrows->remark }}</span></li>  
                </ul>
            </div>
            <div class="support-profile-box">
                <h5 class="title">{{ __("Payment Details") }}</h5>
                <ul class="support-profile-list">
                    <li>{{ __("Fees & Charge") }} : <span>{{ get_amount($escrows->escrowDetails->fee,$escrows->escrow_currency) }}</span></li>
                    <li>{{ __("Seller Amount") }} : <span>{{ get_amount($escrows->escrowDetails->seller_get,$escrows->escrow_currency) }}</span></li>
                    @if ($escrows->payment_type == escrow_const()::GATEWAY)
                    <li>{{ __("Pay with") }} : <span>{{ @$escrows->paymentGatewayCurrency->name }}</span></li>  
                    <li>{{ __("Exchange Rate") }} : <span>{{ "1 ".$escrows->escrow_currency." = ".get_amount($escrows->escrowDetails->gateway_exchange_rate,$escrows->paymentGatewayCurrency->currency_code) }}</span></li>  
                    <li>{{ __("Buyer Paid") }} : <span>{{ get_amount($escrows->escrowDetails->buyer_pay,$escrows->paymentGatewayCurrency->currency_code) }}</span></li>  
                    @endif
                    @if ($escrows->payment_type == escrow_const()::MY_WALLET)
                    <li>{{ __("Pay with") }} : <span>{{ "My Wallet" }}</span></li>  
                    <li>{{ __("Exchange Rate") }} : <span>{{ "1 ".$escrows->escrow_currency." = 1 ".$escrows->escrow_currency }}</span></li>  
                    <li>{{ __("Buyer Paid") }} : <span>{{ get_amount($escrows->escrowDetails->buyer_pay, $escrows->escrow_currency) }}</span></li>  
                    @endif
                </ul>
            </div>
        </div>
    </div> 

 @push('script')
 <script>
    $(".releaseToBuyer").click(function(){
        var actionRoute =  "{{ setRoute('admin.escrow.release.payment','buyer') }}";
        var target      = "{{ $escrows->id }}";
        var message     = `Are you sure to <strong>release this payment to the buyer</strong>?`;

        openAlertModal(actionRoute,target,message,"Confirm","POST");
    });
    $(".releaseToSeller").click(function(){
        var actionRoute =  "{{ setRoute('admin.escrow.release.payment','seller') }}";
        var target      = "{{ $escrows->id }}";
        var message     = `Are you sure to <strong>release this payment to the seller</strong>?`;

        openAlertModal(actionRoute,target,message,"Confirm","POST");
    });
</script>
 @endpush
    

 