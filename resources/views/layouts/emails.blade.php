<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta name="viewport" content="width=device-width" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>@yield('title')</title>
        <link href="https://s3.amazonaws.com/tpv-assets/email-styles.css" media="all" rel="stylesheet" type="text/css" />
    </head>
    <body itemscope itemtype="http://schema.org/EmailMessage">
        <table class="body-wrap">
            <tr>
                <td></td>
                <td class="container" width="600">
                    <div class="content">
                        @yield('content')
                    </div>
                </td>
                <td></td>
            </tr>
        </table>

        <br />
    </body>
</html>
