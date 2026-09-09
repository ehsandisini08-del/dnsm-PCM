<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Sensitive attributes that must never be stored in audit logs.
     */
    protected const SENSITIVE_ATTRIBUTES = [
        'password',
        'remember_token',
        'api_key',
        'secret',
        'token',
    ];

    /**
     * Record an audit log entry.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): AuditLog {
        $user = $userId ?? Auth::id();
        $ip = Request::ip() ?? '127.0.0.1';
        $userAgent = Request::userAgent();

        $cleanedOld = $oldValues !== null ? $this->filterSensitiveAttributes($oldValues) : null;
        $cleanedNew = $newValues !== null ? $this->filterSensitiveAttributes($newValues) : null;

        return AuditLog::create([
            'user_id' => $user,
            'action' => strtoupper($action),
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'old_values' => $cleanedOld,
            'new_values' => $cleanedNew,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Remove sensitive attributes from array before persisting.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function filterSensitiveAttributes(array $data): array
    {
        foreach (self::SENSITIVE_ATTRIBUTES as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '********';
            }
        }

        return $data;
    }
}
