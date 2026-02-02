<?php

namespace App\Jobs\Enrollment;

use App\Models\Student;
use App\Models\Task;
use App\Services\EnrollmentDocumentService;
use App\Traits\Progressable;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class ExportEnrollmentDocumentsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Progressable;

    public int $timeout = 3600; // 60 minutes
    public int $tries = 3;
    public int $maxExceptions = 3;
    public bool $failOnTimeout = true;

    public function __construct(Task $task)
    {
        $this->setTask($task);
        $this->onQueue('background');
    }

    public function handle(): void
    {
        try {
            $this->initProgress($this->task, 100, 'Initializing document export...');

            $parameters = $this->task->parameters ?? [];
            $termId = $parameters['term_id'] ?? null;
            $academicId = $parameters['academic_id'] ?? null;
            $nationalId = $parameters['national_id'] ?? null;
            $programId = $parameters['program_id'] ?? null;
            $levelId = $parameters['level_id'] ?? null;

            if (empty($termId)) {
                throw new Exception('term_id is required.');
            }

            $this->setProgress(10, 'Fetching students with enrollments...');

            // Determine mode
            $isIndividual = !empty($academicId) || !empty($nationalId);

            if ($isIndividual) {
                $query = Student::query();
                if (!empty($academicId)) {
                    $query->where('academic_id', $academicId);
                }
                if (!empty($nationalId)) {
                    $query->where('national_id', $nationalId);
                }
                // Filter to only students who have enrollments in this term
                $query->whereHas('enrollments', function ($q) use ($termId) {
                    $q->where('term_id', $termId);
                });
            } else {
                if (empty($programId) || empty($levelId)) {
                    throw new Exception('For group export please provide both program_id and level_id.');
                }
                // Filter to only students who have enrollments in this term
                $query = Student::query()
                    ->where('program_id', $programId)
                    ->where('level_id', $levelId)
                    ->whereHas('enrollments', function ($q) use ($termId) {
                        $q->where('term_id', $termId);
                    });
            }

            $students = $query->get();

            $totalStudents = $students->count();

            if ($totalStudents === 0) {
                $this->setProgress(80, 'No students found, creating empty ZIP...');
            } else {
                $this->setProgress(20, 'Generating PDF documents...');
            }

            /** @var EnrollmentDocumentService $documentService */
            $documentService = app(EnrollmentDocumentService::class);

            $processedCount = 0;
            $skippedCount = 0;
            $files = [];

            foreach ($students as $student) {
                if ($this->task->fresh()->status === 'cancelled') {
                    throw new Exception('Export was cancelled by user.');
                }

                try {
                    $result = $documentService->generatePdf($student, $termId);
                    $publicPath = parse_url($result['url'], PHP_URL_PATH);
                    $storagePath = public_path(ltrim($publicPath, '/'));
                    if (file_exists($storagePath)) {
                        $files[$result['filename']] = $storagePath;
                    } else {
                        $skippedCount++;
                    }
                } catch (Exception $e) {
                    $skippedCount++;
                    \Log::warning('Failed to generate PDF for student', [
                        'student_id' => $student->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $processedCount++;
                if ($totalStudents > 0) {
                    $progress = 20 + (int)(($processedCount / $totalStudents) * 60);
                    $this->setProgress($progress, "Processing student {$processedCount} of {$totalStudents}...");
                }
            }

            $this->setProgress(85, 'Creating ZIP archive...');

            $zipName = 'enrollment_documents_' . Carbon::now()->format('Ymd_His') . '.zip';
            $tempZipPath = 'temp/' . $zipName;
            $fullTempPath = Storage::disk('local')->path($tempZipPath);

            // Ensure temp directory exists
            Storage::disk('local')->makeDirectory('temp');

            $zip = new ZipArchive();
            if ($zip->open($fullTempPath, ZipArchive::CREATE) !== true) {
                throw new Exception('Failed to create zip archive.');
            }

            foreach ($files as $name => $path) {
                $zip->addFile($path, $name);
            }

            $zip->close();

            $this->setProgress(95, 'Finalizing export...');

            // Move to permanent location
            $permanentPath = 'exports/' . $zipName;
            Storage::disk('local')->makeDirectory('exports');
            Storage::disk('local')->move($tempZipPath, $permanentPath);

            $documentsCount = count($files);
            $message = $documentsCount > 0
                ? 'Export completed. Generated ' . $documentsCount . ' document(s)' . ($skippedCount > 0 ? ', skipped ' . $skippedCount : '') . '.'
                : 'Export completed. No documents were generated (no students with enrollments found).';

            $this->task->update([
                'status' => 'completed',
                'progress' => 100,
                'result' => [
                    'file_path' => $permanentPath,
                    'filename' => $zipName,
                    'total_students' => $totalStudents,
                    'documents_generated' => $documentsCount,
                    'skipped' => $skippedCount,
                    'download_url' => route('enrollments.exportDocuments.download', ['uuid' => $this->task->uuid]),
                ],
                'message' => $message,
            ]);

        } catch (Throwable $e) {
            $this->task->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'message' => 'Export failed: ' . $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
