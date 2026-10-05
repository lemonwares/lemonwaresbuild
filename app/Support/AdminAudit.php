<?php

namespace App\Support;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Writes the admin audit log: who did what, when, from where, and the submitted values.
 */
class AdminAudit
{
    /**
     * Form fields whose values are never stored.
     */
    private const SECRET_FIELDS = ['password', 'token', 'secret', 'api_key', 'apikey', 'epp', 'hash', 'credential', 'private'];

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function record(string $action, ?User $admin, ?Request $request = null, array $payload = [], ?int $statusCode = null, ?string $subjectType = null, ?string $subjectId = null): void
    {
        $request ??= request();

        try {
            AdminAuditLog::query()->create([
                'admin_user_id' => $admin?->id,
                'admin_email' => $admin?->email ?? (is_string($request->input('email')) ? strtolower($request->input('email')) : null),
                'action' => $action,
                'method' => $request->getMethod(),
                'route_name' => $request->route()?->getName(),
                'url' => mb_substr($request->fullUrl(), 0, 500),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'payload' => $payload === [] ? null : self::redact($payload),
                'status_code' => $statusCode,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Records a change made through an admin route (called by the middleware after the response).
     */
    public static function recordRequest(Request $request, int $statusCode): void
    {
        $route = $request->route();
        $routeName = (string) $route?->getName();

        [$subjectType, $subjectId] = [null, null];
        foreach ((array) $route?->parameters() as $name => $value) {
            $subjectType = $value instanceof Model ? class_basename($value) : $name;
            $subjectId = $value instanceof Model ? (string) $value->getKey() : (string) $value;
            break;
        }

        $action = str_replace('admin.', '', $routeName) ?: strtolower($request->getMethod());

        self::record(
            $action,
            AdminPermissions::currentUser(),
            $request,
            $request->except(['_token', '_method']),
            $statusCode,
            $subjectType,
            $subjectId,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function redact(array $payload): array
    {
        $clean = [];
        foreach ($payload as $key => $value) {
            $lower = strtolower((string) $key);
            $isSecret = collect(self::SECRET_FIELDS)->contains(fn ($needle) => str_contains($lower, $needle));

            if ($isSecret) {
                $clean[$key] = filled($value) ? '[hidden]' : null;
            } elseif (is_array($value)) {
                $clean[$key] = self::redact($value);
            } elseif (is_object($value)) {
                $clean[$key] = '[file]';
            } else {
                $clean[$key] = is_string($value) ? mb_substr($value, 0, 500) : $value;
            }
        }

        return $clean;
    }
}
