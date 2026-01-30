<?php

namespace App\Traits;

use App\Models\Task;
use Illuminate\Support\Facades\Log;

trait Progressable
{
    /**
     * The task model associated with the job.
     *
     * @var Task|null
     */
    protected ?Task $task = null;

    /**
     * Total number of steps for progress calculation.
     *
     * @var int
     */
    protected int $totalSteps = 0;

    /**
     * Current step for progress tracking.
     *
     * @var int
     */
    protected int $currentStep = 0;

    /**
     * Metadata to be included in the result payload (not status messages).
     *
     * @var array
     */
    protected array $metadata = [];

    /**
     * Set the task for progress tracking.
     *
     * @param Task $task
     * @return static
     */
    public function setTask(Task $task): static
    {
        $this->task = $task;
        return $this;
    }

    /**
     * Get the current task.
     *
     * @return Task|null
     */
    public function getTask(): ?Task
    {
        return $this->task;
    }

    /**
     * Set the total steps for progress calculation.
     *
     * @param int $steps
     * @return static
     */
    public function setTotalSteps(int $steps): static
    {
        $this->totalSteps = max(1, $steps);
        return $this;
    }

    /**
     * Get total steps.
     *
     * @return int
     */
    public function getTotalSteps(): int
    {
        return $this->totalSteps;
    }

    /**
     * Get current step.
     *
     * @return int
     */
    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    /**
     * Add metadata to be included in the final result.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addMetadata(string $key, mixed $value): static
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Set multiple metadata values at once.
     *
     * @param array $metadata
     * @return static
     */
    public function setMetadata(array $metadata): static
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    /**
     * Get all metadata.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Start processing the task.
     *
     * @param string|null $message Optional status message
     * @return static
     */
    protected function startProgress(?string $message = null): static
    {
        if (!$this->task) {
            return $this;
        }

        $this->task->update([
            'status' => 'processing',
            'progress' => 0,
            'message' => $message,
        ]);

        return $this;
    }

    /**
     * Update progress by incrementing steps.
     *
     * @param int $increment Number of steps to increment (default 1)
     * @param string|null $message Optional status message
     * @return static
     */
    protected function updateProgress(int $increment = 1, ?string $message = null): static
    {
        if (!$this->task || $this->totalSteps <= 0) {
            return $this;
        }

        $this->currentStep = min($this->currentStep + $increment, $this->totalSteps);
        $progress = (int) (($this->currentStep / $this->totalSteps) * 100);

        $updateData = ['progress' => $progress];
        if ($message !== null) {
            $updateData['message'] = $message;
        }

        $this->task->update($updateData);

        if ($progress % 10 === 0 || $progress === 100) {
            Log::info('Task progress updated', [
                'task_id' => $this->task->id,
                'progress' => $progress,
                'step' => "{$this->currentStep}/{$this->totalSteps}",
            ]);
        }

        return $this;
    }

    /**
     * Set progress to a specific percentage.
     *
     * @param int $percentage Progress percentage (0-100)
     * @param string|null $message Optional status message
     * @return static
     */
    protected function setProgress(int $percentage, ?string $message = null): static
    {
        if (!$this->task) {
            return $this;
        }

        $progress = max(0, min(100, $percentage));
        $updateData = ['progress' => $progress];

        if ($message !== null) {
            $updateData['message'] = $message;
        }

        $this->task->update($updateData);

        return $this;
    }

    /**
     * Complete the task successfully with optional result data.
     *
     * @param array $result Additional result data to merge with metadata
     * @param string|null $message Optional completion message
     * @return static
     */
    protected function completeProgress(array $result = [], ?string $message = null): static
    {
        if (!$this->task) {
            return $this;
        }

        $finalResult = array_merge($this->metadata, $result);

        $this->task->update([
            'status' => 'completed',
            'progress' => 100,
            'message' => $message ?? 'Task completed successfully',
            'result' => $finalResult,
        ]);

        Log::info('Task completed', ['task_id' => $this->task->id]);

        return $this;
    }

    /**
     * Mark the task as failed.
     *
     * @param \Throwable|string $error The exception or error message
     * @param array $context Additional context for the error
     * @return static
     */
    protected function failProgress(\Throwable|string $error, array $context = []): static
    {
        $errorMessage = $error instanceof \Throwable ? $error->getMessage() : $error;
        
        $userMessage = $this->sanitizeErrorMessage($errorMessage);

        if (!$this->task) {
            Log::error('Task failed but no task set', [
                'error' => $errorMessage,
                'context' => $context,
            ]);
            return $this;
        }

        $errorDetails = [
            'error_message' => $errorMessage,
            'error_type' => $error instanceof \Throwable ? get_class($error) : 'string',
            'error_context' => $context,
        ];

        if ($error instanceof \Throwable) {
            $errorDetails['error_file'] = $error->getFile();
            $errorDetails['error_line'] = $error->getLine();
            $errorDetails['error_trace'] = $error->getTraceAsString();
        }

        $this->task->update([
            'status' => 'failed',
            'progress' => $this->task->progress,
            'message' => $userMessage,
            'result' => array_merge($this->metadata, $errorDetails),
        ]);

        Log::error('Task failed', [
            'task_id' => $this->task->id,
            'error' => $errorMessage,
            'context' => $context,
        ]);

        return $this;
    }

    /**
     * Sanitize error message for user-friendly display.
     *
     * @param string $errorMessage
     * @return string
     */
    protected function sanitizeErrorMessage(string $errorMessage): string
    {
        if (strlen($errorMessage) > 200) {
            $errorMessage = substr($errorMessage, 0, 197) . '...';
        }

        $errorMessage = preg_replace('/\/[^\s]+\.php/', '[file]', $errorMessage);
        $errorMessage = preg_replace('/Stack trace:.*$/s', '', $errorMessage);
        $errorMessage = preg_replace('/in \/.*? on line \d+/', '', $errorMessage);

        $friendlyMessages = [
            '/file not found/i' => 'The specified file could not be found',
            '/permission denied/i' => 'Permission denied to access the file',
            '/connection/i' => 'Connection error occurred',
            '/timeout/i' => 'Operation timed out',
            '/memory/i' => 'Insufficient memory to complete the operation',
        ];

        foreach ($friendlyMessages as $pattern => $friendly) {
            if (preg_match($pattern, $errorMessage)) {
                return $friendly;
            }
        }

        return trim($errorMessage) ?: 'An error occurred while processing the task';
    }

    /**
     * Handle job failure (called by Laravel queue system).
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        $this->failProgress($exception);
    }

    /**
     * Cancel the task.
     *
     * @param string|null $reason Optional cancellation reason
     * @return static
     */
    protected function cancelProgress(?string $reason = null): static
    {
        if (!$this->task) {
            return $this;
        }

        $this->task->update([
            'status' => 'cancelled',
            'message' => $reason ?? 'Task was cancelled',
            'result' => $this->metadata,
        ]);

        return $this;
    }

    /**
     * Check if the task has been cancelled.
     *
     * @return bool
     */
    protected function isCancelled(): bool
    {
        if (!$this->task) {
            return false;
        }

        $this->task->refresh();
        return $this->task->status === 'cancelled';
    }

    /**
     * Create a new task for progress tracking.
     *
     * @param string $type General task type (e.g., export, import)
     * @param int $userId The user ID associated with the task
     * @param array $parameters Additional parameters for the task
     * @param string|null $subtype Optional subtype for more specific categorization
     * @return Task The created task
     */
    public static function createTask(string $type, int $userId, array $parameters = [], ?string $subtype = null): Task
    {
        return Task::create([
            'type' => $type,
            'subtype' => $subtype,
            'user_id' => $userId,
            'status' => 'queued',
            'progress' => 0,
            'parameters' => $parameters,
        ]);
    }

    /**
     * Initialize progress tracking with task and total steps.
     *
     * @param Task $task
     * @param int $totalSteps
     * @param string|null $message Optional start message
     * @return static
     */
    protected function initProgress(Task $task, int $totalSteps, ?string $message = null): static
    {
        $this->setTask($task);
        $this->setTotalSteps($totalSteps);
        $this->startProgress($message);

        return $this;
    }
}