<?php

namespace App\Jobs;

use App\Models\NotificationBatch;
use App\Models\TeacherEvaluationStatus;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationBatchJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    protected $batchId;

    protected $isRetry;

    protected $detailId;

    public function __construct($batchId, $isRetry = false, $detailId = null)
    {
        $this->batchId = $batchId;
        $this->isRetry = $isRetry;
        $this->detailId = $detailId;
    }

    /**
     * Replica el renderText() del frontend:
     * - *texto* → <strong>
     * - _texto_ → <em>
     * - líneas de curso → chip azul estilo preview
     * - saltos de línea → <br>
     */
    private function renderBody(string $text): string
    {
        $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $html = preg_replace('/\*([^*\n]+)\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/_([^_\n]+)_/', '<em>$1</em>', $html);

        $lines = explode("\n", $html);
        $lines = array_map(function ($line) {
            if (preg_match('/^- .+\(Ciclo: .+, Grupo: .+\)/', $line)) {
                $content = preg_replace('/^- /', '', $line);

                return '<span style="display:inline-flex;align-items:center;gap:6px;'
                    .'background:#f0f7ff;border-left:3px solid #0078d4;'
                    .'border-radius:4px;padding:2px 8px;margin:1px 0;'
                    .'font-size:0.8rem;color:#0078d4;font-weight:500;">'
                    .$content.'</span>';
            }

            return $line;
        }, $lines);

        return implode('<br>', $lines);
    }

    public function handle()
    {
        set_time_limit(0);

        $batch = NotificationBatch::with(['details.teacher', 'office', 'campus'])
            ->find($this->batchId);

        if (! $batch) {
            return;
        }

        // 🔒 Si ya está completado totalmente Y no quedan skipped → no hacer nada
        if ($batch->status === NotificationBatch::STATUS_COMPLETED) {
            $hasSkipped = $batch->details()->where('status', 'skipped')->exists();
            if (! $hasSkipped) {
                return;
            }
        }

        // 🎯 Seleccionar qué detalles procesar
        $withTeacher = fn ($q) => $q->withTrashed();

        if ($this->detailId) {

            // Envío individual (inicial pendiente, reenvío fallido, u omitido por falta de correo)
            $details = $batch->details()
                ->with(['teacher' => $withTeacher])
                ->where('id', $this->detailId)
                ->whereIn('status', ['pending', 'failed', 'skipped'])
                ->get();

        } elseif ($this->isRetry) {

            // Reintento masivo (fallidos y omitidos por falta de correo)
            $details = $batch->details()
                ->with(['teacher' => $withTeacher])
                ->whereIn('status', ['failed', 'skipped'])
                ->get();

        } else {

            // Primer envío (solo pendientes)
            $details = $batch->details()
                ->with(['teacher' => $withTeacher])
                ->where('status', 'pending')
                ->get();
        }

        foreach ($details as $detail) {

            try {

                $teacher = $detail->teacher;

                if (! $teacher || ! $teacher->email) {
                    $detail->update(['status' => 'skipped']);

                    continue;
                }

                $courses = TeacherEvaluationStatus::where('teacher_id', $teacher->id)
                    ->where('import_batch_id', $batch->import_batch_id)
                    ->where('expired_components', '>', 0)
                    ->with(['course', 'campus'])
                    ->get();

                $courseList = $courses->map(function ($c) {
                    return "- {$c->course?->name} (Ciclo: {$c->cycle}, Grupo: {$c->group}) - {$c->campus?->name}";
                })->implode("\n");

                $body = str_replace(
                    ['{docente}', '{cursos}'],
                    [$teacher->full_name, $courseList ?: '- (sin cursos)'],
                    $batch->body
                );

                $office = $batch->office;

                $htmlBody = $this->renderBody($body);
                $signatureUrl = null;
                if ($office?->signature) {
                    $path = storage_path('app/public/'.$office->signature);
                    if (file_exists($path)) {
                        $mime = mime_content_type($path);
                        $data = base64_encode(file_get_contents($path));
                        $signatureUrl = "data:{$mime};base64,{$data}";
                    }
                }

                $viewData = [
                    'subject' => $batch->subject ?? 'Notificación de rubros vencidos',
                    'body' => $htmlBody,
                    'officeName' => $office?->name ?? '',
                    'officeEmail' => $office?->email ?? '',
                    'campusName' => $batch->campus?->name ?? '',
                    'signatureUrl' => $signatureUrl,
                    'sentAt' => now()->format('d/m/Y H:i'),
                ];

                $htmlContent = view('emails.notification_batch', $viewData)->render();

                Mail::html($htmlContent, function ($message) use ($teacher, $batch, $office) {
                    $message->to($teacher->email)
                        ->subject($batch->subject ?? 'Notificación de rubros vencidos');

                    if ($office?->email) {
                        $message->from($office->email, $office->name);
                    }

                    if ($office?->cc_email) {
                        $message->cc($office->cc_email);
                    }
                });

                $detail->update([
                    'status' => 'sent',
                    'sent_at' => Carbon::now(),
                ]);

                // Respetar límite de 1 email/seg de Mailtrap (plan gratuito)
                usleep(1200000);

            } catch (\Throwable $e) {

                Log::error('SendNotificationBatchJob failed', [
                    'batch_id' => $this->batchId,
                    'detail_id' => $detail->id,
                    'teacher' => optional($detail->teacher)->full_name,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                $detail->update([
                    'status' => 'failed',
                ]);
            }
        }

        // 🔄 Recalcular estado del batch
        $total = $batch->details()->count();
        $sent = $batch->details()->where('status', 'sent')->count();
        $skipped = $batch->details()->where('status', 'skipped')->count();
        $failed = $batch->details()->where('status', 'failed')->count();
        $pending = $total - $sent - $skipped - $failed;

        if ($pending === 0) {
            // Solo se marca como completado cuando todos fueron enviados exitosamente.
            // Si quedan omitidos (sin correo) o fallidos, se deja como completed_with_errors
            // para permitir reintentos cuando los correos sean registrados.
            $batch->update([
                'status' => ($failed > 0 || $skipped > 0)
                    ? NotificationBatch::STATUS_COMPLETED_WITH_ERRORS
                    : NotificationBatch::STATUS_COMPLETED,
            ]);
        }
    }
}
