@extends('layouts.emails')

@section('title')
Send EzTPV Contract to Customer
@endsection

@section('content')
    <table class="main" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="content-wrap">
                <table width="100%" cellpadding="0" cellspacing="0">
                    @if (!is_null($logo))
                        <tr>
                            <td class="content-block aligncenter">
                                <img id="logo" src="{{ config('services.aws.cloudfront.domain') }}/{{ $logo }}" height="80px" alt="Logo">
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="content-block">
                            {!! $message_body !!}
                        </td>
                    </tr>
                    <tr>
                        <td class="content-block aligncenter">
                            <p>
                                @if ($language === 'spanish')
                                    Para ver sus archivos adjuntos, por favor haga clic en el siguiente enlace:
                                @else
                                    To view your attachments, please click the following link:
                                @endif
                            <p>
                            <p>
                                <a href="{{ $url }}" class="btn-primary">
                                    @if ($language === 'spanish')
                                        Haz click aquí para proceder
                                    @else
                                        Click here to proceed
                                    @endif
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
