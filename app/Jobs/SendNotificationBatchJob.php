<?php

namespace App\Jobs;

use App\Models\NotificationBatch;
use App\Models\TeacherEvaluationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

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

    public function handle()
    {
        $batch = NotificationBatch::with(['details.teacher'])
            ->find($this->batchId);

        if (!$batch) return;

        // 🔒 Si ya está completado totalmente → no hacer nada
        if ($batch->status === NotificationBatch::STATUS_COMPLETED) {
            return;
        }

        // 🎯 Seleccionar qué detalles procesar
        if ($this->detailId) {

            // Reenvío individual
            $details = $batch->details()
                ->where('id', $this->detailId)
                ->where('status', 'failed')
                ->get();

        } elseif ($this->isRetry) {

            // Reintento masivo (solo fallidos)
            $details = $batch->details()
                ->where('status', 'failed')
                ->get();

        } else {

            // Primer envío (solo pendientes)
            $details = $batch->details()
                ->where('status', 'pending')
                ->get();
        }

        foreach ($details as $detail) {

            try {

                $teacher = $detail->teacher;

                if (!$teacher || !$teacher->email) {
                    throw new \Exception('Correo no disponible');
                }

                $courses = TeacherEvaluationStatus::where('teacher_id', $teacher->id)
                    ->where('import_batch_id', $batch->import_batch_id)
                    ->where('expired_components', '>', 0)
                    ->get();

                $courseList = $courses->map(function ($c) {
                    return "- Curso ID: {$c->course_id}";
                })->implode("\n");

                $body = str_replace(
                    ['{docente}', '{cursos}'],
                    [$teacher->full_name, $courseList],
                    $batch->body
                );

                Mail::raw($body, function ($message) use ($teacher, $batch) {
                    $message->to($teacher->email)
                        ->subject($batch->subject ?? 'Notificación de rubros vencidos');
                });

                $detail->update([
                    'status' => 'sent',
                    'sent_at' => Carbon::now()
                ]);

            } catch (\Throwable $e) {

                $detail->update([
                    'status' => 'failed'
                ]);
            }
        }

        // 🔄 Recalcular estado del batch
        $total = $batch->details()->count();
        $sent = $batch->details()->where('status', 'sent')->count();
        $failed = $batch->details()->where('status', 'failed')->count();

        if ($sent === $total) {

            $batch->update([
                'status' => NotificationBatch::STATUS_COMPLETED
            ]);

        } elseif ($failed > 0) {

            $batch->update([
                'status' => NotificationBatch::STATUS_COMPLETED_WITH_ERRORS
            ]);

        } else {

            $batch->update([
                'status' => NotificationBatch::STATUS_ACTIVE
            ]);
        }
    }
}