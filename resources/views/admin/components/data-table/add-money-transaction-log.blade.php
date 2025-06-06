<table class="custom-table transaction-search-table">
    <thead>
        <tr> 
            <th>{{ __("SL")}}</th>
            <th>{{ __("Transaction ID")}}</th>
            <th>{{ __("Email")}}</th>
            <th>{{ __("Username")}}</th> 
            <th>{{ __("Amount")}}</th>
            <th>{{ __("Method")}}</th>
            <th>{{ __("Status")}}</th>
            <th>{{ __("time")}}</th>
            <th>{{ __("action")}}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transactions  as $key => $item)
            <tr>  
                <td>{{ $transactions->firstItem()+$loop->index}}</td>
                <td>{{ $item->trx_id }}</td>
                <td>{{ $item->user->email }}</td>
                <td>{{ $item->user->username }}</td> 
                <td>{{ $item->currency->symbol.$item->sender_request_amount }}</td>
                <td><span class="text--info">{{ @$item['gateway_currency']->name }}</span></td>
                <td><span class="{{ $item->stringStatus->class }}">{{ $item->stringStatus->value }}</span></td>
                <td>{{ $item->created_at->format('d-m-y h:i:s A') }}</td>
                <td>
                    @include('admin.components.link.info-default',[
                        'href'          => setRoute('admin.add.money.details', $item->id),
                        'permission'    => "admin.add.money.details",
                    ])
                </td>
            </tr>
        @empty
        @endforelse
    </tbody>
</table>