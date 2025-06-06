<table class="custom-table transaction-search-table">
    <thead>
        <tr> 
            <th>{{ __("SL")}}</th>
            <th>{{ __("Escrow Id")}}</th>
            <th>{{ __("Title")}}</th> 
            <th>{{ __("Role")}}</th> 
            <th>{{ __("Amount")}}</th>
            <th>{{ __("category")}}</th>
            <th>{{ __("Charge")}}</th>
            <th>{{ __("Charge Payer")}}</th>
            <th>{{ __("Status")}}</th>
            <th>{{ __("time")}}</th>
            <th>{{ __("action")}}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($escrows  as $key => $item)
            <tr>  
                <td>{{ $escrows->firstItem()+$loop->index}}</td>
                <td>{{ $item->escrow_id }}</td>
                <td>{{ substr($item->title,0,30)."..." }}</td>
                <td class="text-capitalize">{{ $item->role}}</td>
                <td>{{ number_format($item->amount,2) ." ". $item->escrow_currency}}</td>  
                <td>{{ $item->escrowCategory->name}}</td>  
                <td>{{ number_format($item->escrowDetails->fee,2) ." ". $item->escrow_currency}}</td>   
                <td><span class="{{ $item->string_who_will_pay->class}} text-capitalize">{{ __($item->string_who_will_pay->value)}}</td>
                <td><span class="{{ $item->string_status->class}}">{{ $item->string_status->value}}</td>
                <td>{{ $item->created_at->format('d-m-y h:i:s A') }}</td>
                <td>
                    @if ($item->status == escrow_const()::ACTIVE_DISPUTE)
                    <a href="{{ setRoute('admin.escrow.chat', $item->id) }}" class="btn btn--primary"><i class="las la-comment"></i></a>
                    @endif 
                    @include('admin.components.link.info-default',[
                        'href'          => setRoute('admin.escrow.details', $item->id),
                        'permission'    => "admin.add.money.details",
                    ])
                </td>
            </tr>
        @empty
        @endforelse
    </tbody>
</table>