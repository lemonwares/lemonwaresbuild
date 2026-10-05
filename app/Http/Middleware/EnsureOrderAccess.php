<?php

namespace App\Http\Middleware;

use App\Models\HostingLead;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Order pages open only with a valid signed link or for the signed-in account that owns the order.
 */
class EnsureOrderAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        $order = collect($request->route()?->parameters() ?? [])
            ->first(fn ($value) => $value instanceof Model);

        $user = $request->user();

        if ($order && $user && ! $user->isAdmin() && $this->owns($user, $order)) {
            return $next($request);
        }

        abort(403);
    }

    protected function owns($user, Model $order): bool
    {
        if ($order instanceof HostingLead) {
            return $order->belongsToCustomer($user);
        }

        $ownerId = (int) ($order->getAttribute('user_id') ?? 0);

        return $ownerId > 0 && $ownerId === (int) $user->accountOwner()->id;
    }
}
