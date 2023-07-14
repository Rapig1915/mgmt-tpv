@extends('layouts.app')

@section('title')
Report: Daily Billing Figures
@endsection

@section('content')
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Reports</li>
        <li class="breadcrumb-item active">Daily Billing Figures</li>
    </ol>

    <div class="container-fluid">
        <!-- navlist include goes here -->

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active">
            	<div class="animated fadeIn">
                    <div class="row page-buttons">
                        <div class="col-md-12"></div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-th-large"></i> Daily Billing Figures
                        </div>
                        <div class="card-body">
                            @if(Session::has('flash_message'))
                                <div class="alert alert-success"><span class="fa fa-check-circle"></span><em> {!! session('flash_message') !!}</em></div>
                            @endif
                            <div class="form-group">
                                {{ Form::open(['method' => 'POST', 'route' => ['reports.report_daily_billing_figures'], 'class' => 'form-inline pull-right']) }}
                                    {{ Form::label('brand_id', 'Brand&nbsp;&nbsp;') }}
                                    {{ Form::select('brand_id', $brands, @$brand_id, ['placeholder' => 'Select a brand', 'class' => 'form-control']) }}&nbsp;&nbsp;
                                    {{ Form::label('date_from', 'Date From&nbsp;&nbsp;') }}
                                    {{ Form::text('date_from', @$date_from, array('class' => 'datepicker form-control', 'id' => 'date_from')) }} 
                                    {{ Form::label('date_to', '&nbsp;&nbsp;To&nbsp;&nbsp;') }}
                                    {{ Form::text('date_to', @$date_to, array('class' => 'datepicker form-control', 'id' => 'date_to')) }} &nbsp;&nbsp;
                                    {{ Form::button('Filter Report', ['type' => 'submit', 'class' => 'btn btn-primary']) }}
                                {{ Form::close() }}
                            </div>
                            <br /><br />
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Market</th>
                                        <th>State</th>
                                        <th>Channel</th>
                                        <th>Language</th>
                                        <th>Call Time</th>
                                        <th>Call Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($calls->isEmpty())
                                        <tr><td colspan="3" class="text-center">No interactions were found.</td></tr>
                                    @else
                                        @foreach ($calls as $call)
                                            <tr>
                                                <td>{{ $call->company }}</td>
                                                <td>{{ $call->market }}</td>
                                                <td>{{ $call->state }}</td>
                                                <td>{{ $call->channel }}</td>
                                                <td>{{ $call->language }}</td>
                                                <td>{{ $call->calltime }}</td>
                                                <td>{{ $call->calltotal }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/.col-->
@endsection

@section('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
@endsection

@section('scripts')
<script src="http://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script>
    $(function() {
        $( ".datepicker" ).datepicker({
            changeMonth: true,
            changeYear: true
        });
    });
</script>
@endsection
