<table class="custom-table escrow-category-search-table">
    <thead>
        <tr> 
            <th>{{ __("Name")}}</th>
            <th>{{ __("Slug")}}</th>  
            <th>{{ __("Status")}}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($escrowCategories ?? [] as $item)
        <tr data-item="{{ $item->editData }}">
            <td>{{ $item->name}}</td>
            <td>{{ $item->slug}}</td> 
            <td>
                @include('admin.components.form.switcher',[
                    'name'          => 'category_status',
                    'value'         => $item->status,
                    'options'       => [__('Enable') => 1,__('Disable') => 0],
                    'onload'        => true,
                    'data_target'       => $item->id,
                    'permission'    => "admin.escrow.category.status.update",
                ])
            </td>
            <td>
                @include('admin.components.link.edit-default',[
                    'href'          => "javascript:void(0)",
                    'class'         => "edit-modal-button",
                    'permission'    => "admin.escrow.category.update",
                ]) 
                @include('admin.components.link.delete-default',[
                    'href'          => "javascript:void(0)",
                    'class'         => "delete-modal-button",
                    'permission'    => "admin.escrow.category.delete",
                ]) 
            </td>
        </tr>
        @empty
            @include('admin.components.alerts.empty',['colspan' => 7])
        @endforelse
    </tbody>
</table> 