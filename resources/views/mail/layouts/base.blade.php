<!DOCTYPE html>
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if !mso]><!-- -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!--<![endif]-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="format-detection" content="date=no" />
    <meta name="format-detection" content="address=no" />
    <meta name="format-detection" content="email=no" />
    <meta name="x-apple-disable-message-reformatting" />
    <link href="https://fonts.googleapis.com/css?family=Gugi:ital,wght@0,400;0,400" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Figtree:ital,wght@" rel="stylesheet" />
    <title>@yield('title', 'Neon')</title>
    <style>
        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100% !important;
            width: 100% !important;
            -webkit-font-smoothing: antialiased;
        }

        * {
            -ms-text-size-adjust: 100%;
        }

        #outlook a {
            padding: 0;
        }

        .ReadMsgBody,
        .ExternalClass {
            width: 100%;
        }

        .ExternalClass,
        .ExternalClass p,
        .ExternalClass td,
        .ExternalClass div,
        .ExternalClass span,
        .ExternalClass font {
            line-height: 100%;
        }

        table,
        td,
        th {
            mso-table-lspace: 0 !important;
            mso-table-rspace: 0 !important;
            border-collapse: collapse;
        }

        u+.body table,
        u+.body td,
        u+.body th {
            will-change: transform;
        }

        body,
        td,
        th,
        p,
        div,
        li,
        a,
        span {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            mso-line-height-rule: exactly;
        }

        img {
            border: 0;
            outline: 0;
            line-height: 100%;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
        }

        .body .pc-project-body {
            background-color: transparent !important;
        }

        @media (min-width: 621px) {
            .pc-lg-hide {
                display: none;
            }

            .pc-lg-bg-img-hide {
                background-image: none !important;
            }
        }

        @media (max-width: 620px) {
            .pc-project-body {
                min-width: 0px !important;
            }

            .pc-project-container {
                width: 100% !important;
            }

            .pc-sm-hide,
            .pc-w620-gridCollapsed-1>tbody>tr>.pc-sm-hide {
                display: none !important;
            }

            .pc-sm-bg-img-hide {
                background-image: none !important;
            }

            .pc-w620-font-size-30px {
                font-size: 30px !important;
            }

            .pc-w620-line-height-133pc {
                line-height: 133% !important;
            }

            .pc-w620-font-size-16px {
                font-size: 16px !important;
            }

            .pc-w620-line-height-163pc {
                line-height: 163% !important;
            }

            .pc-w620-padding-35-35-35-35 {
                padding: 35px 35px 35px 35px !important;
            }

            .pc-w620-itemsSpacings-40-40 {
                padding-left: 20px !important;
                padding-right: 20px !important;
                padding-top: 20px !important;
                padding-bottom: 20px !important;
            }

            .pc-w620-valign-top {
                vertical-align: top !important;
            }

            .pc-w620-halign-center {
                text-align: center !important;
            }

            .pc-w620-align-center {
                text-align: center !important;
                text-align-last: center !important;
            }

            .pc-w620-itemsSpacings-10-0 {
                padding-left: 5px !important;
                padding-right: 5px !important;
                padding-top: 0px !important;
                padding-bottom: 0px !important;
            }

            .pc-w620-valign-middle {
                vertical-align: middle !important;
            }
        }

        @media (max-width: 520px) {
            .pc-w520-padding-30-30-30-30 {
                padding: 30px 30px 30px 30px !important;
            }
        }

        /* Custom styles */
        .neon-brand {
            font-family: 'Gugi', Arial, Helvetica, sans-serif;
            font-size: 36px;
            color: #53eafd;
            font-weight: 400;
        }

        .neon-text {
            font-family: 'Figtree', Arial, Helvetica, sans-serif;
            color: #ffffff;
        }

        .neon-bg-dark {
            background-color: #1B1B1B;
        }

        .neon-bg-light {
            background-color: #f4f4f4;
        }

        .neon-link {
            color: #1595e7;
            text-decoration: none;
            font-weight: 500;
        }

        .neon-button {
            background-color: #53eafd;
            color: #1B1B1B;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
            font-family: 'Figtree', Arial, Helvetica, sans-serif;
        }

        .content-section {
            padding: 20px 0;
        }

        .content-white {
            background-color: #ffffff;
            color: #2D3A41;
            padding: 40px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .content-white h1 {
            color: #1B1B1B;
            font-family: 'Figtree', Arial, Helvetica, sans-serif;
            font-size: 24px;
            margin: 0 0 16px 0;
        }

        .content-white h2 {
            color: #1B1B1B;
            font-family: 'Figtree', Arial, Helvetica, sans-serif;
            font-size: 20px;
            margin: 24px 0 12px 0;
        }

        .content-white p {
            font-family: 'Figtree', Arial, Helvetica, sans-serif;
            line-height: 1.6;
            margin: 12px 0;
        }

        .content-white ul {
            font-family: 'Figtree', Arial, Helvetica, sans-serif;
            line-height: 1.6;
            margin: 12px 0;
            padding-left: 20px;
        }

        @media (max-width: 620px) {
            .content-white {
                padding: 20px;
                margin: 10px 0;
            }
        }
    </style>
    <!--[if !mso]><!-- -->
    <style>
        @font-face {
            font-family: 'Gugi';
            font-style: normal;
            font-weight: 400;
            src: url('https://fonts.gstatic.com/s/gugi/v20/A2BVn5dXywshZAmK8w.woff') format('woff'), url('https://fonts.gstatic.com/s/gugi/v20/A2BVn5dXywshZAmK9Q.woff2') format('woff2');
        }
    </style>
    <!--<![endif]-->
    <!--[if mso]>
    <style type="text/css">
        .pc-font-alt {
            font-family: Arial, Helvetica, sans-serif !important;
        }
    </style>
    <![endif]-->
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
</head>

<body class="body pc-font-alt neon-bg-light">
    <table class="pc-project-body neon-bg-light" style="table-layout: fixed; min-width: 600px;" width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
        <tr>
            <td align="center" valign="top">
                <table class="pc-project-container" align="center" width="600" style="width: 600px; max-width: 600px;" border="0" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="padding: 20px 0px 20px 0px;" align="left" valign="top">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%" style="width: 100%;">
                                <!-- Header -->
                                @include('mail.partials.header', ['title' => $title ?? null])

                                <!-- Main Content -->
                                <tr>
                                    <td valign="top">
                                        @yield('content')
                                    </td>
                                </tr>

                                <!-- Footer -->
                                @include('mail.partials.footer')
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>