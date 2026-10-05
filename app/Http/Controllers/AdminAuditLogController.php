<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $adminId = (int) $request->query('admin', 0);
        $search = trim((string) $request->query('q', ''));
        $kind = (string) $request->query('kind', '');

        $logs = AdminAuditLog::query()
            ->with('admin')
            ->when($adminId > 0, fn (Builder $q) => $q->where('admin_user_id', $adminId))
            ->when($kind === 'logins', fn (Builder $q) => $q->whereIn('action', ['login', 'login_failed', 'logout']))
            ->when($kind === 'failed', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner->where('action', 'login_failed')->orWhere('status_code', '>=', 400)))
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('action', 'like', '%'.$search.'%')
                        ->orWhere('url', 'like', '%'.$search.'%')
                        ->orWhere('admin_email', 'like', '%'.$search.'%')
                        ->orWhere('subject_id', $search);
                });
            })
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.audit-log.index', [
            'logs' => $logs,
            'admins' => User::query()->where('role', 'admin')->orderBy('name')->get(['id', 'name']),
            'adminId' => $adminId,
            'search' => $search,
            'kind' => $kind,
        ]);
    }
}
