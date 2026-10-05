<?php

namespace App\Http\Controllers;

use App\Support\AdminPermissions;
use App\Support\CloudflareSettings;
use App\Support\CloudinarySettings;
use App\Support\FlutterwaveSettings;
use App\Support\HetznerClient;
use App\Support\TrekMailSettings;
use App\Support\WhmcsClient;
use App\Support\ZeptoMailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Health of outside services, background jobs, scheduled tasks, error log and backups.
 */
class AdminSystemController extends Controller
{
    /**
     * Scheduled tasks an admin may run on demand => description.
     */
    public const TASKS = [
        'whmcs:sync' => 'Sync customers and services from WHMCS (runs every 15 minutes)',
        'email:expire-orders' => 'Deactivate email orders whose paid period has ended (runs daily at 01:15)',
    ];

    public function index(): View
    {
        return view('admin.system.index', [
            'services' => [
                ['name' => 'WHMCS (billing)', 'ok' => WhmcsClient::isConfigured(), 'href' => route('admin.whmcs-settings.index')],
                ['name' => 'Flutterwave (payments)', 'ok' => FlutterwaveSettings::isConfigured(), 'href' => route('admin.flutterwave-settings.index')],
                ['name' => 'ZeptoMail (outgoing email)', 'ok' => ZeptoMailSettings::isConfigured(), 'href' => route('admin.zeptomail-settings.index')],
                ['name' => 'TrekMail (mailboxes)', 'ok' => TrekMailSettings::isConfigured(), 'href' => route('admin.email-provider-settings.index')],
                ['name' => 'Cloudflare (DNS)', 'ok' => CloudflareSettings::isConfigured(), 'href' => route('admin.cloudflare-settings.index')],
                ['name' => 'Cloudinary (images)', 'ok' => CloudinarySettings::isConfigured(), 'href' => route('admin.cloudinary-settings.index')],
                ['name' => 'Hetzner (VPS)', 'ok' => HetznerClient::isConfigured(), 'href' => route('admin.hetzner-settings.index')],
            ],
            'tasks' => self::TASKS,
            'pendingJobs' => $this->safeCount('jobs'),
            'failedJobs' => $this->failedJobs(),
            'logLines' => $this->logTail(120),
            'canBackup' => $this->sqlitePath() !== null,
            'environment' => [
                'App environment' => (string) config('app.env'),
                'Debug mode' => config('app.debug') ? 'ON (turn off in production)' : 'off',
                'Database' => (string) config('database.default'),
                'Queue' => (string) config('queue.default'),
                'PHP' => PHP_VERSION,
                'Laravel' => app()->version(),
            ],
        ]);
    }

    public function runTask(Request $request): RedirectResponse
    {
        $data = $request->validate(['task' => ['required', Rule::in(array_keys(self::TASKS))]]);

        try {
            $exit = Artisan::call($data['task']);
            $output = trim(Artisan::output());
        } catch (\Throwable $e) {
            report($e);
            $exit = 1;
            $output = $e->getMessage();
        }

        return redirect()->route('admin.system.index')->with('task_result', [
            'task' => $data['task'],
            'ok' => $exit === 0,
            'output' => mb_substr($output ?: 'Finished with no output.', 0, 4000),
        ]);
    }

    public function retryJob(Request $request): RedirectResponse
    {
        $data = $request->validate(['uuid' => ['required', 'string', 'max:64']]);
        Artisan::call('queue:retry', ['id' => [$data['uuid']]]);

        return redirect()->route('admin.system.index')->with('status', 'Job sent back to the queue.');
    }

    public function forgetJob(Request $request): RedirectResponse
    {
        $data = $request->validate(['uuid' => ['required', 'string', 'max:64']]);
        Artisan::call('queue:forget', ['id' => $data['uuid']]);

        return redirect()->route('admin.system.index')->with('status', 'Failed job removed.');
    }

    public function clearCache(): RedirectResponse
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        return redirect()->route('admin.system.index')->with('status', 'Application cache and compiled pages cleared.');
    }

    public function backup(): BinaryFileResponse|RedirectResponse
    {
        // The database holds API keys and customer data: super admins only.
        abort_unless(AdminPermissions::currentUser()?->isSuperAdmin(), 403, 'Only super admins can download backups.');

        $path = $this->sqlitePath();
        if ($path === null) {
            return redirect()->route('admin.system.index')->withErrors(['backup' => 'Download backups are only available for the SQLite database. Use your hosting provider\'s database backups.']);
        }

        $copy = storage_path('app/private/backup-'.now()->format('Ymd-His').'.sqlite');
        @mkdir(dirname($copy), 0775, true);

        DB::connection()->getPdo()->exec('VACUUM INTO '.DB::connection()->getPdo()->quote($copy));

        return response()->download($copy, 'lemonwares-'.now()->format('Y-m-d-His').'.sqlite')->deleteFileAfterSend();
    }

    private function sqlitePath(): ?string
    {
        if (config('database.default') !== 'sqlite') {
            return null;
        }

        $path = (string) config('database.connections.sqlite.database');

        return $path !== '' && $path !== ':memory:' && is_file($path) ? $path : null;
    }

    private function safeCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return Collection<int, object>
     */
    private function failedJobs()
    {
        try {
            return DB::table('failed_jobs')->latest('failed_at')->limit(50)->get()->map(function ($job) {
                $payload = json_decode((string) $job->payload, true);
                $job->name = (string) data_get($payload, 'displayName', 'Job');
                $job->error = strtok((string) $job->exception, "\n");

                return $job;
            });
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Last lines of the application log, newest last.
     *
     * @return list<string>
     */
    private function logTail(int $lines): array
    {
        $file = storage_path('logs/laravel.log');
        if (! is_file($file)) {
            return [];
        }

        $handle = fopen($file, 'r');
        $size = filesize($file);
        $chunk = min($size, 256 * 1024);
        fseek($handle, -$chunk, SEEK_END);
        $text = (string) fread($handle, $chunk);
        fclose($handle);

        $all = preg_split('/\r?\n/', $text) ?: [];
        $entries = array_values(array_filter($all, fn ($line) => preg_match('/^\[\d{4}-\d{2}-\d{2}/', $line)));

        return array_slice(array_map(fn ($line) => mb_substr($line, 0, 400), $entries), -$lines);
    }
}
