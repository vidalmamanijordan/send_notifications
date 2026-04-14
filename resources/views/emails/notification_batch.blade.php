<!DOCTYPE html>
<html lang="es" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        .email-header {
            background-color: #003765 !important;
            background-image: url('{{ config('app.url') }}/images/email/header-gradient.png') !important;
            background-size: cover !important;
        }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#f3f4f6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" role="presentation"
                    style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">

                    {{-- ===== HEADER con degradado ===== --}}
                    <tr>
                        {{-- bgcolor es el fallback para Outlook; el CSS aplica en Gmail/Apple Mail/etc --}}
                        <td class="email-header" bgcolor="#003765" valign="top"
                            background="{{ config('app.url') }}/images/email/header-gradient.png"
                            style="padding:0;background-color:#003765;background-image:url('{{ config('app.url') }}/images/email/header-gradient.png');background-size:cover;background-position:center;">

                            {{-- VML: imagen de fondo para Outlook 2007-2019 --}}
                            <!--[if gte mso 9]>
                            <v:rect xmlns:v="urn:schemas-microsoft-com:vml"
                                    fill="true" stroke="false"
                                    style="mso-width-percent:1000;">
                                <v:fill type="frame"
                                        src="{{ config('app.url') }}/images/email/header-gradient.png"
                                        size="1,1" aspect="atmost" />
                                <v:textbox inset="0,0,0,0" style="mso-fit-shape-to-text:true">
                            <![endif]-->

                                {{-- Acento dorado superior --}}
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                                    <tr>
                                        <td bgcolor="#f8a900" height="3"
                                            style="background-color:#f8a900;background-image:linear-gradient(90deg,#e09500,#f8a900,#ffd166,#f8a900,#e09500);height:3px;font-size:0;line-height:0;">&nbsp;</td>
                                    </tr>
                                </table>

                                {{-- Contenido del header --}}
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                                    <tr>
                                        <td align="center" style="padding:38px 48px 34px;">

                                            {{-- Label institución --}}
                                            <p style="margin:0 0 4px;font-size:8px;font-weight:700;color:#f8a900;letter-spacing:4px;text-transform:uppercase;font-family:Arial,sans-serif;">
                                                Universidad Peruana Uni&oacute;n
                                            </p>

                                            {{-- Nombre de la oficina --}}
                                            <p style="margin:0 0 6px;font-size:22px;font-weight:300;color:#ffffff;letter-spacing:0.8px;line-height:1.3;font-family:Arial,sans-serif;">
                                                {{ $officeName }}
                                            </p>

                                            {{-- Línea decorativa dorada --}}
                                            <table cellpadding="0" cellspacing="0" border="0" role="presentation" align="center" style="margin:10px auto 12px;">
                                                <tr>
                                                    <td width="16" height="1" bgcolor="#8a6200"
                                                        style="background-color:#8a6200;width:16px;height:1px;font-size:0;line-height:0;">&nbsp;</td>
                                                    <td width="6" align="center" style="padding:0 4px;">
                                                        <table cellpadding="0" cellspacing="0" border="0" role="presentation">
                                                            <tr>
                                                                <td width="4" height="4" bgcolor="#f8a900"
                                                                    style="background-color:#f8a900;width:4px;height:4px;border-radius:50%;font-size:0;line-height:0;">&nbsp;</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td width="16" height="1" bgcolor="#8a6200"
                                                        style="background-color:#8a6200;width:16px;height:1px;font-size:0;line-height:0;">&nbsp;</td>
                                                </tr>
                                            </table>

                                            {{-- Correo --}}
                                            <p style="margin:0;font-size:11px;color:#7da8c7;letter-spacing:0.8px;font-family:Arial,sans-serif;">
                                                {{ $officeEmail }}
                                            </p>

                                        </td>
                                    </tr>
                                </table>

                            <!--[if gte mso 9]>
                                </v:textbox>
                            </v:rect>
                            <![endif]-->

                        </td>
                    </tr>

                    {{-- ASUNTO --}}
                    <tr>
                        <td style="padding:20px 28px 0;border-bottom:1px solid #f0f0f0;">
                            <p style="margin:0 0 16px;font-size:16px;font-weight:700;color:#111827;font-family:Arial,sans-serif;">
                                {{ $subject }}
                            </p>
                        </td>
                    </tr>

                    {{-- CUERPO --}}
                    <tr>
                        <td style="padding:20px 28px;">
                            <div style="font-size:14px;line-height:1.7;color:#374151;font-family:Arial,sans-serif;">
                                {!! $body !!}
                            </div>
                        </td>
                    </tr>

                    {{-- FIRMA (imagen) --}}
                    @if ($signatureUrl)
                        <tr>
                            <td style="padding:0 28px 24px;">
                                <img src="{{ $signatureUrl }}" alt="Firma"
                                    style="max-height:90px;display:block;">
                            </td>
                        </tr>
                    @endif

                    {{-- FOOTER --}}
                    <tr>
                        <td bgcolor="#f9fafb" style="background-color:#f9fafb;padding:14px 28px;border-top:1px solid #f0f0f0;">
                            <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;font-family:Arial,sans-serif;">
                                Este correo fue generado autom&aacute;ticamente. Por favor no responda a este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>
