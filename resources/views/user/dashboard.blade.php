@extends('user.layouts.master') 
@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Dashboard")])
@endsection
@section('content') 
    <div class="body-wrapper">
        <div class="dashboard-area mt-20">
            <div class="dashboard-item-area">
                <div class="dashboard-item-slider">
                    <div class="swiper-wrapper">
                        @foreach ($userWallet as $item) 
                        <div class="swiper-slide">
                            <div class="dashbord-item">
                                <div class="dashboard-content">
                                    <span class="sub-title">{{ $item->currency->name}} - <span class="text--base">{{ $item->currency->code}}</span></span>
                                    <h3 class="title">
                                        {{ $item->currency->symbol}}
                                        @if ($item->currency->type == "CRYPTO")
                                        {{ get_amount($item->balance,null,8) }}
                                        @else
                                        {{ get_amount($item->balance,null,2) }}
                                        @endif 
                                    </h3>
                                </div>
                                <div class="dashboard-icon">
                                    <img src="{{ get_image($item->currency->flag,'currency-flag') }}" alt="flag">
                                </div>
                            </div>
                        </div> 
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-area mt-20">
            <div class="dashboard-item-area">
                <div class="row mb-20-none">
                    <h4 class="title">{{ __("State") }}</h4>
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                        <div class="dashbord-item">
                            <div class="dashboard-content">
                                <span class="sub-title">{{ __("Total Escrow") }}</span>
                                <h3 class="title">{{ $state->total_escrow }}</h3>
                            </div>
                            <div class="dashboard-icon">
                                <i class="las la-handshake"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                        <div class="dashbord-item">
                            <div class="dashboard-content">
                                <span class="sub-title">{{ __('Completed Escrow') }}</span>
                                <h3 class="title">{{ $state->compledted_escrow }}</h3>
                            </div>
                            <div class="dashboard-icon">
                                <i class="las la-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                        <div class="dashbord-item">
                            <div class="dashboard-content">
                                <span class="sub-title">{{ __('Pending Escrow') }}</span>
                                <h3 class="title">{{ $state->pending_escrow }}</h3>
                            </div>
                            <div class="dashboard-icon">
                                <i class="las la-spinner"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                        <div class="dashbord-item">
                            <div class="dashboard-content">
                                <span class="sub-title">{{ __('Disputed Escrow') }}</span>
                                <h3 class="title">{{ $state->dispute_escrow }}</h3>
                            </div>
                            <div class="dashboard-icon">
                                <i class="las la-times-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="chart-area mt-20">
            <div class="row mb-20-none">
                <div class="col-xxl-6 col-xl-6 col-lg-6 mb-20">
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <div id="chart1" class="chart" data-chart_one_data="{{ json_encode($chartData['chart_one_data']) }}" data-month_day="{{ json_encode($chartData['month_day']) }}"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-xl-6 col-lg-6 mb-20">
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <div id="chart2" class="chart" data-chart_two_data="{{ json_encode($chartData['chart_two_data']) }}" data-month_day="{{ json_encode($chartData['month_day']) }}"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-list-area mt-20">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __('Transactions Log') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <a href="{{ setRoute('user.transactions.index') }}" class="btn--base">{{ __('View More') }}</a>
                    </div>
                </div>
            </div>
            @include('user.components.wallets.transation-log', compact('transactions'))
        </div>
    </div>
@endsection 
@push('script')
<script>
    //=====================================================
            //start Chart one
    //======================================================
    var chart1 = $('#chart1');
    var chart_one_data = chart1.data('chart_one_data');
    var month_day = chart1.data('month_day');
    var options = {
        series: [{
            name: 'Released Escrow',
            color: "#44a08d",
            data: chart_one_data.released_escrow_by_month
        }],
        chart: {
            height: 350,
            toolbar: {
                show: false
            },
            type: 'bar',
        },
        plotOptions: {
            bar: {
                borderRadius: 10,
                dataLabels: {
                    position: 'top', // top, center, bottom
                },
            }
        },
        dataLabels: {
            formatter: function (val) {
                return val;
            },
            offsetY: -20,
            style: {
                fontSize: '12px',
                colors: ["#ffffff"]
            }
        },
        xaxis: {
            type: 'datetime',
            categories: month_day,
            position: 'top',
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            crosshairs: {
                fill: {
                    type: 'gradient',
                    gradient: {
                        colorFrom: '#8781c6',
                        colorTo: '#8781c6',
                        stops: [0, 100],
                        opacityFrom: 0.4,
                        opacityTo: 0.5,
                    }
                }
            },
            tooltip: {
                enabled: true,
            }
        },
        yaxis: {
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false,
            },

        },
        title: {
            text: '{{ __("Escrow Overview") }}',
            floating: true,
            offsetY: 330,
            align: 'center',
            style: {
                color: '#fff'
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#chart1"), options);
    chart.render();
    //=====================================================
            //end Chart one
    //======================================================

    //=====================================================
            //start Chart two
    //======================================================
    
    var chart2 = $('#chart2');
    var chart_two_data = chart2.data('chart_two_data');
    var month_day = chart2.data('month_day');
    // console.log(chart_two_data);
    var options = {
        series: [{
            name: '{{ __("Add Money") }}',
            color: "#2167e8",
            data: chart_two_data.add_money
        }, 
        {
            name: '{{ __("Money Out") }}',
            color: "#44a08d",
            data: chart_two_data.money_out
        }, 
        {
            name: '{{ __("Exchange Money") }}',
            color: "#12b883",
            data: chart_two_data.exchange_money
        }],
        chart: {
        type: 'bar',
        toolbar: {
            show: false
        },
        height: 350
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
        tooltip: {
        y: {
            formatter: function (val) {
            return val
            }
        }
        }
    };

    var chart = new ApexCharts(document.querySelector("#chart2"), options);
    chart.render();
    //=====================================================
            //end Chart two
    //======================================================
</script>
@endpush
