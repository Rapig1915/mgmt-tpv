@extends('layouts.app')

@section('title')
Support: Contract Runner
@endsection

@section('content')
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Support</li>
        <li class="breadcrumb-item active">Contract Runner</li>
    </ol>

    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-th-large"></i> Contract Runner
                </div>
                <div class="card-body">
                    <p>
                        Enter confirmation code to attempt to regenerate (and resend) contract documents. Separate multiple codes with commas.
                    </p>
                    @if($multiple === false)
                        <form method="POST">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="col-10">
                                    <input type="text" tabindex="1" class="form-control" name="code" placeholder="Confirmation Code(s)" @if(isset($code)) value="{{$code}}" @endif>
                                </div>
                                <div class="col-2">
                                    
                                    <button tabindex="3" class="btn btn-primary" type="submit">Run <span class="fa fa-arrow-right" ></span></button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input tabindex="2" class="form-check-input ml-auto" type="checkbox" @if(isset($preview)) checked @endif value="true" id="previewCheck" name="preview">
                                        <label class="form-check-label" for="previewCheck">
                                            Is Preview Contract?
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </form>
                        @if(isset($output))
                            <hr>
                            @if(trim($output) == '')
                                <pre class="bg-light p-2">No Command Output</pre>
                                <p class="text-muted">This result typically means the specified confirmation code is not configured to use contracts.</p>
                            @else
                                <h4>Command Output</h4>
                                <pre style="text-align: left;" class="bg-light p-2">{{$output}}</pre>
                                <p class="text-muted">
                                    A successful run will typically end with <em>Success!</em> followed by the URL of the generated contract document.
                                </p>
                            @endif
                        @endif
                    @else
                        <form method="POST">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="col-10">
                                    <input type="text" class="form-control" name="code" placeholder="Confirmation Code(s)">
                                </div>
                                <div class="col-2">
                                    <button class="btn btn-primary" type="submit">Run <span class="fa fa-arrow-right" ></span></button>
                                </div>
                            </div>
                        </form>
                        <hr>
                        @foreach($code as $ccode)
                            <div class="card mb-2">
                                <div class="card-header">
                                    {{ $ccode }}
                                </div>
                                <div class="card-body" id="code-{{$ccode}}">
                                    <span class="fa fa-spinner fa-spin"></span>
                                </div>
                            </div>
                        @endforeach
                        
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @if($multiple) 
        <script type="text/javascript">
            codes = {!! json_encode($code) !!};

            for(let i = 0, len = codes.length; i < len; i += 1) {
                window.axios.post('/support/contract_test', {
                    _token: '{{ csrf_token() }}',
                    format: 'text',
                    code: codes[i],
                }).then((response) => {
                    el = document.getElementById(`code-${codes[i]}`);
                    if(el) {
                        msg = response.data;
                        if(msg === null) {
                            msg = 'No Data Returned';
                        }
                        msg = msg.trim();
                        if(msg.length === 0) {
                            msg = 'No Data Returned';
                        }
                        el.innerHTML = '<pre>'+msg+'</pre>';
                    }
                }).catch((e) => {
                    console.log(e);
                    el = document.getElementById(`code-${codes[i]}`);
                    if(el) {
                        el.innerHTML = e;
                    }
                });
            }
        </script>
    @endif
@endsection
