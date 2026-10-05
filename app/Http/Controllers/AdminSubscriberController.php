<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $subscribers = NewsletterSubscriber::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($inner) => $inner
                ->where('email', 'like', '%'.$search.'%')
                ->orWhere('full_name', 'like', '%'.$search.'%')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.subscribers.index', compact('subscribers', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'full_name' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', Rule::unique('newsletter_subscribers', 'email')],
        ]);

        NewsletterSubscriber::query()->create($data);

        return redirect()->route('admin.subscribers.index')->with('status', $data['email'].' added.');
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return redirect()->back()->with('status', $subscriber->email.' removed from the list.');
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Joined']);
            NewsletterSubscriber::query()->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [$row->full_name, $row->email, $row->created_at?->format('Y-m-d')]);
                }
            });
            fclose($out);
        }, 'lemonwares-subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Imports a CSV with an "email" column (and optionally "name"). Existing emails are skipped.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF")), (array) fgetcsv($handle));
        $emailIndex = array_search('email', $header, true);
        $nameIndex = collect(['name', 'full_name', 'full name'])->map(fn ($k) => array_search($k, $header, true))->first(fn ($i) => $i !== false);

        if ($emailIndex === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'The CSV needs a column called "email".']);
        }

        $added = 0;
        $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $email = strtolower(trim((string) ($row[$emailIndex] ?? '')));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || NewsletterSubscriber::query()->where('email', $email)->exists()) {
                $skipped++;

                continue;
            }

            NewsletterSubscriber::query()->create([
                'email' => $email,
                'full_name' => $nameIndex !== null ? mb_substr(trim((string) ($row[$nameIndex] ?? '')), 0, 160) : null,
            ]);
            $added++;
        }
        fclose($handle);

        return redirect()->route('admin.subscribers.index')->with('status', $added.' added, '.$skipped.' skipped (invalid or already on the list).');
    }
}
