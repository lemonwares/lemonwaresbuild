<?php

namespace App\Filament\Support;

use App\Models\User;
use App\Support\Whmcs\Whmcs;
use App\Support\Whmcs\WhmcsResult;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ConsoleAction
{
    public static function admin(): ?User
    {
        $user = Auth::guard('console')->user();

        return $user instanceof User ? $user : null;
    }

    public static function can(string $level = 'edit'): bool
    {
        return (bool) self::admin()?->hasAdminPermission('whmcs', $level);
    }

    /**
     * A console button that is only shown to admins with the given WHMCS access level.
     */
    public static function make(string $name, string $level = 'edit'): Action
    {
        return Action::make($name)->visible(fn (): bool => self::can($level));
    }

    /**
     * Reloads a mirrored record after a write; the post-write sync may have removed it.
     */
    public static function reload(Model $record): void
    {
        if ($record->newQuery()->whereKey($record->getKey())->exists()) {
            $record->refresh();
        }
    }

    /**
     * Runs a WHMCS write as the signed-in admin and reports the outcome.
     *
     * @param  array<string, mixed>  $params
     */
    public static function run(string $action, array $params, string $success, string $level = 'edit'): bool
    {
        return self::call($action, $params, $success, $level)->ok;
    }

    /**
     * Same as run(), but returns the WHMCS response for actions whose result must be shown.
     *
     * @param  array<string, mixed>  $params
     */
    public static function call(string $action, array $params, string $success, string $level = 'edit'): WhmcsResult
    {
        if (! self::can($level)) {
            Notification::make()->danger()->title('You do not have permission to do that.')->send();

            return WhmcsResult::failure('Permission denied.');
        }

        $result = Whmcs::call($action, $params, self::admin());

        $result->ok
            ? Notification::make()->success()->title($success)->send()
            : Notification::make()->danger()->title('WHMCS refused the request')->body($result->error)->persistent()->send();

        return $result;
    }

    /**
     * Read-only lookup used to prefill console forms. Returns null (and warns) when WHMCS fails.
     *
     * @param  array<string, mixed>  $params
     */
    public static function fetch(string $action, array $params): ?WhmcsResult
    {
        $result = Whmcs::call($action, $params);
        if (! $result->ok) {
            Notification::make()->warning()->title('Could not load current values from WHMCS')->body($result->error)->send();

            return null;
        }

        return $result;
    }
}
