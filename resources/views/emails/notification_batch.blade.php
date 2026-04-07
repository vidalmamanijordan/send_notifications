<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>

<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                    style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">

                    {{-- HEADER --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#2596be,#1a7a9e);padding:24px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p
                                            style="margin:0;font-size:11px;font-weight:600;color:rgba(255,255,255,0.75);letter-spacing:0.5px;text-transform:uppercase;">
                                            Universidad Peruana Unión
                                        </p>
                                        <p style="margin:4px 0 0;font-size:16px;font-weight:700;color:#ffffff;">
                                            {{ $officeName }}
                                        </p>
                                        <p style="margin:4px 0 0;font-size:12px;color:rgba(255,255,255,0.8);">
                                            {{ $officeEmail }}
                                        </p>
                                    </td>
                                    <td align="right" style="vertical-align:top;">
                                        <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.7);">
                                            {{ $sentAt }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ASUNTO --}}
                    <tr>
                        <td style="padding:20px 28px 0;border-bottom:1px solid #f0f0f0;">
                            <p style="margin:0 0 16px;font-size:16px;font-weight:700;color:#111827;">
                                {{ $subject }}
                            </p>
                        </td>
                    </tr>

                    {{-- CUERPO --}}
                    <tr>
                        <td style="padding:20px 28px;">
                            <div style="font-size:14px;line-height:1.7;color:#374151;">
                                {!! $body !!}
                            </div>
                        </td>
                    </tr>

                    {{-- FIRMA (imagen) --}}
                    @if ($signatureUrl)
                        <tr>
                            <td style="padding:0 28px 24px;">
                                <img src="{{ $signatureUrl }}" alt="Firma"
                                    style="max-height:90px;object-fit:contain;display:block;">
                            </td>
                        </tr>
                    @endif

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background:#f9fafb;padding:14px 28px;border-top:1px solid #f0f0f0;">
                            <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;">
                                Este correo fue generado automáticamente. Por favor no responda a este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>
