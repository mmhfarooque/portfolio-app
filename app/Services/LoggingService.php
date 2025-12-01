<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Throwable;

class LoggingService
{
    protected static ?float $startTime = null;

    /**
     * Start timing for performance measurement.
     */
    public static function startTiming(): void
    {
        self::$startTime = microtime(true);
    }

    /**
     * Get elapsed time in milliseconds.
     */
    protected static function getElapsedTime(): ?float
    {
        if (self::$startTime === null) {
            return null;
        }
        return round((microtime(true) - self::$startTime) * 1000, 2);
    }

    /**
     * Log an activity.
     */
    public static function activity(
        string $action,
        ?string $message = null,
        ?Model $model = null,
        array $context = []
    ): ActivityLog {
        return self::log(
            ActivityLog::TYPE_ACTIVITY,
            ActivityLog::LEVEL_INFO,
            $action,
            $message,
            $model,
            $context
        );
    }

    /**
     * Log an info message.
     */
    public static function info(
        string $action,
        ?string $message = null,
        ?Model $model = null,
        array $context = []
    ): ActivityLog {
        return self::log(
            ActivityLog::TYPE_INFO,
            ActivityLog::LEVEL_INFO,
            $action,
            $message,
            $model,
            $context
        );
    }

    /**
     * Log a warning.
     */
    public static function warning(
        string $action,
        ?string $message = null,
        ?Model $model = null,
        array $context = []
    ): ActivityLog {
        return self::log(
            ActivityLog::TYPE_WARNING,
            ActivityLog::LEVEL_WARNING,
            $action,
            $message,
            $model,
            $context
        );
    }

    /**
     * Log a debug message.
     */
    public static function debug(
        string $action,
        ?string $message = null,
        ?Model $model = null,
        array $context = []
    ): ActivityLog {
        return self::log(
            ActivityLog::TYPE_DEBUG,
            ActivityLog::LEVEL_DEBUG,
            $action,
            $message,
            $model,
            $context
        );
    }

    /**
     * Log an error.
     */
    public static function error(
        string $action,
        ?string $message = null,
        ?Throwable $exception = null,
        ?Model $model = null,
        array $context = []
    ): ActivityLog {
        $data = [
            'type' => ActivityLog::TYPE_ERROR,
            'level' => ActivityLog::LEVEL_ERROR,
            'action' => $action,
            'message' => $message ?? ($exception ? $exception->getMessage() : null),
            'context' => $context,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'user_agent' => substr(Request::userAgent() ?? '', 0, 500),
            'url' => substr(Request::fullUrl() ?? '', 0, 500),
            'method' => Request::method(),
            'duration_ms' => self::getElapsedTime(),
            'memory_usage' => memory_get_peak_usage(true),
        ];

        if ($model) {
            $data['loggable_type'] = get_class($model);
            $data['loggable_id'] = $model->getKey();
        }

        if ($exception) {
            $data['exception_class'] = get_class($exception);
            $data['exception_message'] = substr($exception->getMessage(), 0, 1000);
            $data['exception_trace'] = substr($exception->getTraceAsString(), 0, 5000);
            $data['file'] = $exception->getFile();
            $data['line'] = $exception->getLine();

            // If it's a critical error (like database issues), mark it
            if (str_contains(get_class($exception), 'Database') ||
                str_contains(get_class($exception), 'PDO')) {
                $data['level'] = ActivityLog::LEVEL_CRITICAL;
            }
        }

        return ActivityLog::create($data);
    }

    /**
     * Log a critical error.
     */
    public static function critical(
        string $action,
        ?string $message = null,
        ?Throwable $exception = null,
        array $context = []
    ): ActivityLog {
        $log = self::error($action, $message, $exception, null, $context);
        $log->update(['level' => ActivityLog::LEVEL_CRITICAL]);
        return $log;
    }

    /**
     * Generic log method.
     */
    protected static function log(
        string $type,
        string $level,
        string $action,
        ?string $message = null,
        ?Model $model = null,
        array $context = []
    ): ActivityLog {
        $data = [
            'type' => $type,
            'level' => $level,
            'action' => $action,
            'message' => $message,
            'context' => $context,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'user_agent' => substr(Request::userAgent() ?? '', 0, 500),
            'url' => substr(Request::fullUrl() ?? '', 0, 500),
            'method' => Request::method(),
            'duration_ms' => self::getElapsedTime(),
            'memory_usage' => memory_get_peak_usage(true),
        ];

        if ($model) {
            $data['loggable_type'] = get_class($model);
            $data['loggable_id'] = $model->getKey();
        }

        return ActivityLog::create($data);
    }

    /**
     * Log a photo upload.
     */
    public static function photoUploaded(Model $photo, array $context = []): ActivityLog
    {
        return self::activity(
            'photo.uploaded',
            "Photo '{$photo->title}' uploaded successfully",
            $photo,
            array_merge($context, [
                'file_size' => $photo->file_size,
                'dimensions' => "{$photo->width}x{$photo->height}",
            ])
        );
    }

    /**
     * Log a photo upload failure.
     */
    public static function photoUploadFailed(string $filename, Throwable $exception, array $context = []): ActivityLog
    {
        return self::error(
            'photo.upload_failed',
            "Failed to upload photo: {$filename}",
            $exception,
            null,
            array_merge($context, ['filename' => $filename])
        );
    }

    /**
     * Log user login.
     */
    public static function userLogin(Model $user): ActivityLog
    {
        return self::activity(
            'user.login',
            "User '{$user->name}' logged in",
            $user
        );
    }

    /**
     * Log user logout.
     */
    public static function userLogout(Model $user): ActivityLog
    {
        return self::activity(
            'user.logout',
            "User '{$user->name}' logged out",
            $user
        );
    }

    /**
     * Log settings updated.
     */
    public static function settingsUpdated(array $changes = []): ActivityLog
    {
        return self::activity(
            'settings.updated',
            'Site settings were updated',
            null,
            ['changes' => $changes]
        );
    }

    /**
     * Log a model created.
     */
    public static function modelCreated(Model $model, ?string $description = null): ActivityLog
    {
        $modelName = class_basename($model);
        return self::activity(
            strtolower($modelName) . '.created',
            $description ?? "{$modelName} created",
            $model
        );
    }

    /**
     * Log a model updated.
     */
    public static function modelUpdated(Model $model, array $changes = [], ?string $description = null): ActivityLog
    {
        $modelName = class_basename($model);
        return self::activity(
            strtolower($modelName) . '.updated',
            $description ?? "{$modelName} updated",
            $model,
            ['changes' => $changes]
        );
    }

    /**
     * Log a model deleted.
     */
    public static function modelDeleted(Model $model, ?string $description = null): ActivityLog
    {
        $modelName = class_basename($model);
        return self::activity(
            strtolower($modelName) . '.deleted',
            $description ?? "{$modelName} deleted",
            $model
        );
    }
}
