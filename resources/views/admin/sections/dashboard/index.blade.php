@extends('admin.layouts.master')

@push('css')

@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Dashboard")])
@endsection

@section('content')
    <div class="dashboard-area">
        <div class="dashboard-item-area">
            <div class="row">
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Total Escrow")}}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ $total_escrow ?? 0}}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--success">{{ __("released")}} {{ $released_escrow ?? 0 }}</span>
                                    <span class="badge badge--warning">{{ __("ongoing")}} {{ $ongoing_escrow ?? 0 }}</span>
                                    <span class="badge badge--danger">{{ __("active Dispute")}} {{ $active_dispute_escrow ?? 0 }}</span>
                                </div>
                            </div>
                            <div class="right"> 
                                 @php
                                 $escrow_percent = 0;
                                   if ($released_escrow != 0 || $total_escrow != 0) {
                                    $escrow_percent = number_format(($released_escrow / $total_escrow) * 100,2);
                                   }  
                               @endphp
                                <div class="chart" id="chart12" data-percent="{{ $escrow_percent ?? 0 }}"><span>{{ $escrow_percent ?? 0 }}%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Add Money Balance")}}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ get_default_currency_symbol() }}{{ number_format($active_add_money+$pending_add_money,2) ?? 0}}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--info">{{ __("Completed")}} {{ get_default_currency_symbol() }}{{ number_format($active_add_money,2) ?? 0}}</span>
                                    <span class="badge badge--warning">{{ __("pending")}} {{ get_default_currency_symbol() }}{{ number_format($pending_add_money,2) ?? 0}}</span>
                                </div>
                            </div>
                            <div class="right">
                                @php
                                  $add_money_percent = 0;
                                    if ($active_add_money != 0 || $pending_add_money != 0) {
                                        $add_money_percent = number_format(($active_add_money / ($active_add_money+$pending_add_money)) * 100,2);
                                    }  
                                @endphp
                                <div class="chart" id="chart8" data-percent="{{ $add_money_percent ?? 0 }}"><span>{{ $add_money_percent ?? 0 }}%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Money Out Balance")}}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ get_default_currency_symbol() }}{{ number_format($active_money_out+$pending_money_out,2) ?? 0}}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--info">{{ __("Completed")}} {{ get_default_currency_symbol() }}{{ number_format($active_money_out,2) ?? 0}}</span>
                                    <span class="badge badge--warning">{{ __("pending")}} {{ get_default_currency_symbol() }}{{ number_format($pending_money_out,2) ?? 0}}</span>
                                </div>
                            </div>
                            <div class="right">
                                @php
                                    $money_out_percent = 0;
                                    if ($active_money_out != 0 || $pending_money_out != 0) {
                                        $money_out_percent = number_format(($active_money_out / ($active_money_out+$pending_money_out)) * 100,2);
                                    } 
                                @endphp
                                <div class="chart" id="chart6" data-percent="{{ $money_out_percent ?? 0 }}"><span>{{ $money_out_percent ?? 0 }}%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Total Profit")}}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ get_default_currency_symbol() }}{{ number_format($total_profite_amount,2) ?? 0}}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--info">{{ __("This Month")}} {{ get_default_currency_symbol() }}{{ number_format($transaction_profite_this_month_amount,2) ?? 0}}</span>
                                    <span class="badge badge--warning">{{ __("Last Month")}} {{ get_default_currency_symbol() }}{{ number_format($transaction_profite_last_month_amount ,2) ?? 0}}</span>
                                </div>
                            </div>
                            <div class="right">
                                @php
                                    $profit_percent = 0;
                                    if ($transaction_profite_this_month_amount != 0 || $transaction_profite_last_month_amount != 0) {
                                        $profit_percent = number_format(($transaction_profite_this_month_amount / ($transaction_profite_this_month_amount+$transaction_profite_last_month_amount)) * 100,2);
                                    } 
                                @endphp
                                <div class="chart" id="chart9" data-percent="{{ $profit_percent ?? 0}}"><span>{{ $profit_percent ?? 0}}%</span></div>
                            </div>
                        </div>
                    </div>
                </div> 
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Escrow Profit")}}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ get_default_currency_symbol() }}{{ number_format($escrow_profit,2) ?? 0}}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--success">{{ __("released")}} {{ $released_escrow ?? 0 }}</span>
                                    <span class="badge badge--warning">{{ __("ongoing")}} {{ $ongoing_escrow ?? 0 }}</span>
                                    <span class="badge badge--danger">{{ __("active Dispute")}} {{ $active_dispute_escrow ?? 0 }}</span>
                                </div>
                            </div>
                            <div class="right">
                                @php
                                 $escrow_percent = 0;
                                   if ($released_escrow != 0 || $total_escrow != 0) {
                                    $escrow_percent = number_format(($released_escrow / $total_escrow) * 100,2);
                                   }  
                               @endphp
                                <div class="chart" id="chart13" data-percent="{{ $escrow_percent ?? 0}}"><span>{{ $escrow_percent ?? 0}}%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("User Active Tickets")}}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ $total_support_ticket ?? 0}}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--info">{{ __("active")}} {{ $active_support_ticket ?? 0}}</span>
                                    <span class="badge badge--warning">{{ __("pending")}} {{ $pending_support_ticket ?? 0}}</span>
                                    <span class="badge badge--success">{{ __("Solved")}} {{ $solved_support_ticket ?? 0}}</span>
                                </div>
                            </div>
                            <div class="right">
                                @php
                                    $ticket_percent = 0;
                                    if ($active_support_ticket != 0 || $pending_support_ticket != 0) {
                                        $ticket_percent = number_format(($active_support_ticket / ($active_support_ticket+$pending_support_ticket+$solved_support_ticket)) * 100,2);
                                    } 
                                @endphp
                                <div class="chart" id="chart10" data-percent="{{ $ticket_percent ?? 0}}"><span>{{ $ticket_percent ?? 0}}%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Total Users")}}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ $total_user ?? 0}}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--info">{{ __("active")}} {{ $active_user ?? 0}}</span>
                                    <span class="badge badge--warning">{{ __("Disabled")}} {{ $total_user - $active_user ?? 0 }}</span>
                                </div>
                            </div>
                            <div class="right">
                                @php
                                    $user_percent = 0;
                                    if ($active_user != 0) {
                                        $user_percent = number_format(($active_user / $total_user) * 100,2);
                                    } 
                                @endphp
                                <div class="chart" id="chart11" data-percent="{{ $user_percent ?? 0}}"><span>{{ $user_percent ?? 0}}%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="chart-area mt-15">
        <div class="row mb-15-none"> 
            <div class="col-xxl-6 col-xl-6 col-lg-6 mb-15">
                <div class="chart-wrapper">
                    <div class="chart-area-header">
                        <h5 class="title">{{ __("Escrow Chart")}}</h5>
                    </div>
                    <div class="chart-container">
                        <div id="chart3" class="order-chart" data-chart_three_data="{{ json_encode($chart_three_data) }}" data-month_day="{{ json_encode($month_day) }}"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 col-xl-6 col-lg-6 mb-15">
                <div class="chart-wrapper">
                    <div class="chart-area-header">
                        <h5 class="title">{{ __("Add Money Chart")}}</h5>
                    </div>
                    <div class="chart-container">
                        <div id="chart1" class="sales-chart" data-chart_one_data="{{ json_encode($chart_one_data) }}" data-month_day="{{ json_encode($month_day) }}"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-15">
                <div class="chart-wrapper">
                    <div class="chart-area-header">
                        <h5 class="title">{{ __("Money Out Chart")}}</h5>
                    </div>
                    <div class="chart-container">
                        <div id="chart2" data-chart_three_data="{{ json_encode($chart_two_data) }}" data-month_day="{{ json_encode($month_day) }}" class="sales-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-15">
                <div class="chart-wrapper">
                    <div class="chart-area-header">
                        <h5 class="title">{{ __("User Analytics") }}</h5>
                    </div>
                    <div class="chart-container">
                        <div id="chart4" data-chart_four_data="{{ json_encode($chart_four_data) }}" class="balance-chart"></div>
                    </div>
                    <div class="chart-area-footer">
                        <div class="chart-btn">
                            <a href="{{ setRoute('admin.users.index') }}" class="btn--base w-100">{{__("View Users")}}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="table-area mt-15">
        <div class="table-wrapper">
            <div class="table-header"> 
                <h5 class="title">{{ __("Latest Transactions")}}</h5> 
            </div>
            </div>
            <div class="table-responsive">
                @php
                    $transactions = $latest_transactions;
                @endphp
                @include('admin.components.data-table.add-money-transaction-log',[
                    'data'  => $transactions
                ])
            </div> 
        </div>
    </div>
@endsection

@push('script')
    <script>
        // apex-chart
        var chart1 = $('#chart1');
        var chart_one_data = chart1.data('chart_one_data');
        var month_day = chart1.data('month_day');
            // apex-chart
        var options = {
            series: [{
            name: '{{ __("pending")}}',
            color: "#5A5278",
            data: chart_one_data.add_money_pending_data
            }, {
            name: '{{ __("Completed")}}',
            color: "#6F6593",
            data: chart_one_data.add_money_success_data
            }, {
            name: '{{ __("canceled")}}',
            color: "#8075AA",
            data: chart_one_data.add_money_canceled_data
            }],
            chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            toolbar: {
                show: false
            },
            zoom: {
                enabled: true
            }
            },
            responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                position: 'bottom',
                offsetX: -10,
                offsetY: 0
                }
            }
            }],
            plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 10
            },
            },
            xaxis: {
            type: 'datetime',
            categories: month_day,
            },
            legend: {
            position: 'bottom',
            offsetX: 40
            },
            fill: {
            opacity: 1
            }
        };

        var chart = new ApexCharts(document.querySelector("#chart1"), options);
        chart.render();
    </script>
    <script>
        var chart2 = $('#chart2');
        var chart_three_data = chart2.data('chart_three_data');
        var month_day = chart2.data('month_day');
        // apex-chart
        var options = {
        series: [{
        name: '{{ __("pending")}}',
        color: "#5A5278",
        data: chart_three_data.pending_data
        }, {
        name: '{{ __("Completed")}}',
        color: "#6F6593",
        data: chart_three_data.success_data
        }, {
        name: '{{ __("canceled")}}',
        color: "#8075AA",
        data: chart_three_data.canceled_data
        }, {
        name: '{{ __("Hold")}}',
        color: "#A192D9",
        data: chart_three_data.hold_data
        }],
        chart: {
        type: 'bar',
        height: 350,
        stacked: true,
        toolbar: {
            show: false
        },
        zoom: {
            enabled: true
        }
        },
        responsive: [{
        breakpoint: 480,
        options: {
            legend: {
            position: 'bottom',
            offsetX: -10,
            offsetY: 0
            }
        }
        }],
        plotOptions: {
        bar: {
            horizontal: false,
            borderRadius: 10
        },
        },
        xaxis: {
        type: 'datetime',
        categories: month_day,
        },
        legend: {
        position: 'bottom',
        offsetX: 40
        },
        fill: {
        opacity: 1
        }
        };

        var chart = new ApexCharts(document.querySelector("#chart2"), options);
        chart.render()

    </script>
    <script>
        var chart3 = $('#chart3');
        var chart_three_data = chart3.data('chart_three_data');
        var month_day = chart3.data('month_day');
        var options = {
            series: [{
            name: '{{ __("released")}}',
            color: "#5A5278",
            data: chart_three_data.escrow_release_data
            }, {
            name: '{{ __("ongoing")}}',
            color: "#6F6593",
            data: chart_three_data.escrow_ongoing_data
            }, {
            name: '{{ __("active Dispute")}}',
            color: "#8075AA",
            data: chart_three_data.escrow_active_dispute_data
            }],
            chart: {
            type: 'bar',
            toolbar: {
                show: false
            },
            height: 325
            },
            plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 5,
                endingShape: 'rounded'
            },
            },
            dataLabels: {
            enabled: false
            },
            stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
            },
            xaxis: {
            type: 'datetime',
            categories: month_day,
            },
            fill: {
            opacity: 1
            },
            };

            var chart = new ApexCharts(document.querySelector("#chart3"), options);
            chart.render();

    </script>
    <script>
        var chart4 = $('#chart4');
        var chart_four_data = chart4.data('chart_four_data');

        var options = {
        series: chart_four_data,
        chart: {
        width: 350,
        type: 'pie'
        },
        colors: ['#5A5278', '#6F6593', '#8075AA', '#A192D9'],
        labels: ['{{ __("active")}}', '{{ __("Unverified")}}', '{{ __("Banned")}}', '{{ __("All")}}'],
        responsive: [{
        breakpoint: 1480,
        options: {
            chart: {
            width: 280
            },
            legend: {
            position: 'bottom'
            }
        },
        breakpoint: 1199,
        options: {
            chart: {
            width: 380
            },
            legend: {
            position: 'bottom'
            }
        },
        breakpoint: 575,
        options: {
            chart: {
            width: 280
            },
            legend: {
            position: 'bottom'
            }
        }
        }],
        legend: {
        position: 'bottom'
        },
        };

        var chart = new ApexCharts(document.querySelector("#chart4"), options);
        chart.render();
    </script>
@endpush