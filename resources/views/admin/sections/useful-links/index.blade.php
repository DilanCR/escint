@extends('admin.layouts.master')

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Useful Links")])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper"> 
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Page Name</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($useful_links as $item)
                            <tr>
                                <td>{{ $item->title }}</td>
                                <td>
                                    @include('admin.components.form.switcher',[
                                        'name'          => 'status',
                                        'value'         => $item->status,
                                        'options'       => ['Enable' => 1,'Disable' => 0],
                                        'onload'        => true,
                                        'data_target'   => $item->slug,
                                        'permission'    => "admin.setup.pages.status.update",
                                    ])
                                </td>
                                <td>
                                    <a href="{{ setRoute('admin.useful.links.edit', $item->id) }}" class="btn btn--base"><i
                                        class="las la-pencil-alt"></i></a>
                                </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty',['colspan' => 2])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div> 
@endsection

@push('script')
    <script>
        // Switcher
        switcherAjax("{{ setRoute('admin.setup.pages.status.update') }}");
    </script>
@endpush