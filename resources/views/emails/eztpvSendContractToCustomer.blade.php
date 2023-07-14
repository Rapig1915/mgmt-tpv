@extends('layouts.emails')

@section('title')
Send EZTPV Contract to Customer
@endsection

@section('content')
    <table class="main" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="content-wrap">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="content-block">
                            @if ($language === 'spanish')
                                Haga clic en el enlace de abajo para ver los archivos adjuntos de <b>{{$company}}</b>.<br /><br />
                            @else
                                Click the link below to see attachments from <b>{{$company}}</b>.<br /><br />
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="content-block aligncenter">
                            <a href="{{ $url }}" class="btn-primary">
                                @if ($language === 'spanish')
                                    Haz click aquí para proceder
                                @else
                                    Click here to proceed
                                @endif
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
