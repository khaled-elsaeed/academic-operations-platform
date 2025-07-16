<?php

if (!function_exists('formatDate')) {
    /**
     * Format a date using Carbon.
     *
     * @param string|\DateTimeInterface|null $date
     * @param string $format
     * @return string|null
     */
    function formatDate($date, $format = 'd M h:i A')
    {
        if (!$date) {
            return null;
        }
        return \Carbon\Carbon::parse($date)->format($format);
    }
}

if (!function_exists('logError')) {
    /**
     * Log an error in a professional, monitorable format.
     *
     * @param string $context      Context or location of the error (e.g., Class@method)
     * @param \Throwable $exception The exception or error object
     * @param array $extra         Additional contextual data (optional)
     * @return void
     */
    function logError($context, $exception, $extra = [])
    {
        $logData = array_merge([
            'context'      => $context,
            'exception'    => get_class($exception),
            'message'      => $exception->getMessage(),
            'file'         => $exception->getFile(),
            'line'         => $exception->getLine(),
            'trace'        => collect($exception->getTrace())->take(10)->toArray(), // limit trace for readability
            'timestamp'    => now()->toIso8601String(),
            'env'          => app()->environment(),
            'user_id'      => auth()->id() ?? null,
            'monitoring'   => true, // flag for log monitoring systems
        ], $extra);

        // Log to default channel
        \Log::error("[$context] Exception occurred", $logData);

        // Optionally, send to external monitoring (e.g., Sentry, Bugsnag) if configured
        if (function_exists('report') && app()->bound('sentry')) {
            report($exception);
        }
    }
}

if (!function_exists('successResponse')) {
    /**
     * Return a standardized success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    function successResponse($message = 'Success',$data = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ], $code);
    }
}

if (!function_exists('errorResponse')) {
    /**
     * Return a standardized error JSON response.
     *
     * @param string $message
     * @param int $code
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    function errorResponse($message = 'Error',$errors = [], $code = 500,)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
        ], $code);
    }
}

if (!function_exists('formatNumber')) {
    /**
     * Format a number with grouped thousands and optional decimals.
     *
     * @param float|int|null $number
     * @param int $decimals
     * @return string|null
     */
    function formatNumber($number, $decimals = 0)
    {
        if ($number === null) {
            return null;
        }
        return number_format($number, $decimals, '.', ',');
    }
}

if (!function_exists('filterSensitive')) {
    /**
     * Remove sensitive fields from attributes before logging.
     *
     * @param array $attributes
     * @return array
     */
    function filterSensitive($attributes)
    {
        $sensitive = ['password', 'remember_token'];
        return collect($attributes)->except($sensitive)->toArray();
    }
}

if (!function_exists('logAction')) {
    /**
     * Log a user action for auditing purposes.
     *
     * @param string $action
     * @param mixed $model
     * @param array $details
     * @return void
     */
    function logAction($action, $model, $details = [])
    {
        $userId = auth()->id() ?? 'system';
        $modelType = is_object($model) ? get_class($model) : $model;
        $modelId = is_object($model) && isset($model->id) ? $model->id : null;
        $table = is_object($model) && method_exists($model, 'getTable') ? $model->getTable() : null;
        $primaryKey = is_object($model) && method_exists($model, 'getKeyName') ? $model->getKeyName() : 'id';
        $ip = request()->ip() ?? null;
        $userAgent = request()->header('User-Agent') ?? null;

        $log = [
            'user_id' => $userId,
            'action' => $action,
            'model' => $modelType,
            'table' => $table,
            'primary_key' => $primaryKey,
            'model_id' => $modelId,
            'details' => $details,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'timestamp' => now()->toDateTimeString(),
        ];

        \Log::channel('action')->info('Action Log:', $log);
    }
}