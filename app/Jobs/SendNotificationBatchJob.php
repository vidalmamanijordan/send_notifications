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

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function handle()
    {
        $batch = NotificationBatch::with(['details.teacher'])
            ->find($this->batchId);

        if (!$batch) return;

        foreach ($batch->details as $detail) {

            if ($detail->status === 'sent') continue;

            try {

                $teacher = $detail->teacher;

                if (!$teacher || !$teacher->email) {
                    throw new \Exception('Correo no disponible');
                }

                // Cursos vencidos
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
