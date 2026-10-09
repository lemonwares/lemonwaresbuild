<?php

namespace App\Filament\Support;

use App\Models\User;
use App\Support\Whmcs\Whmcs;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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
     * Runs a WHMCS write as the signed-in admin and reports the outcome.
     *
     * @param  array<string, mixed>  $params
     */
    public static function run(string $action, array $params, string $success, string $level = 'edit'): bool
    {
        if (! self::can($level)) {
            Notification::make()->danger()->title('You do not have permission to do that.')->send();

            return false;
        }

        $result = Whmcs::call($action, $params, self::admin());

        $result->ok
            ? Notification::make()->success()->title($success)->send()
            : Notification::make()->danger()->title('WHMCS refused the request')->body($result->error)->persistent()->send();

        return $result->ok;
    }
}
