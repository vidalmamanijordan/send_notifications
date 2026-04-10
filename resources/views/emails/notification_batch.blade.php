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
                        <td style="padding:0;background-color:#003765;background-image:linear-gradient(135deg,#020c1b 0%,#003765 45%,#0e6ba8 100%);">

                            {{-- Acento dorado superior --}}
                            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                                <td style="background-image:linear-gradient(90deg,#f8a900,#ffd166,#f8a900);height:3px;font-size:0;line-height:0;">&nbsp;</td>
                            </tr></table>

                            {{-- Contenido --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:38px 48px 34px;">

                                        {{-- Label institución --}}
                                        <p style="margin:0 0 4px;font-size:8px;font-weight:700;color:rgba(248,169,0,0.75);letter-spacing:4px;text-transform:uppercase;font-family:Arial,sans-serif;">
                                            Universidad Peruana Unión
                                        </p>

                                        {{-- Nombre de la oficina --}}
                                        <p style="margin:0 0 6px;font-size:22px;font-weight:300;color:#ffffff;letter-spacing:0.8px;line-height:1.3;font-family:Arial,sans-serif;">
                                            {{ $officeName }}
                                        </p>

                                        {{-- Línea decorativa dorada --}}
                                        <table cellpadding="0" cellspacing="0" style="margin:10px auto 12px;">
                                            <tr>
                                                <td width="16" style="background-color:rgba(248,169,0,0.35);height:1px;font-size:0;vertical-align:middle;">&nbsp;</td>
                                                <td style="padding:0 5px;font-size:0;vertical-align:middle;">
                                                    <div style="width:4px;height:4px;background-color:#f8a900;border-radius:50%;margin-top:-1px;">&nbsp;</div>
                                                </td>
                                                <td width="16" style="background-color:rgba(248,169,0,0.35);height:1px;font-size:0;vertical-align:middle;">&nbsp;</td>
                                            </tr>
                                        </table>

                                        {{-- Correo --}}
                                        <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.45);letter-spacing:0.8px;font-family:Arial,sans-serif;">
                                            {{ $officeEmail }}
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
