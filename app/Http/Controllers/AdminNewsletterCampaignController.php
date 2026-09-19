<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Support\MediaStorage;
use App\Support\ZeptoMailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminNewsletterCampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = NewsletterCampaign::query()
            ->with('creator:id,name')
            ->latest()
            ->paginate(30);

        $subscriberCount = NewsletterSubscriber::count();

        return view('admin.newsletter-campaigns.index', compact('campaigns', 'subscriberCount'));
    }

    public function create(): View
    {
        return view('admin.newsletter-campaigns.create', [
            'subscriberCount' => NewsletterSubscriber::count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $data['images'] = $this->storeUploadedImages($request, []);

        $campaign = NewsletterCampaign::query()->create([
            ...$data,
            'status' => 'draft',
            'recipient_mode' => 'all',
            'created_by' => (int) $request->session()->get('admin_user_id') ?: null,
        ]);

        return redirect()
            ->route('admin.newsletter-campaigns.show', $campaign)
            ->with('status', 'Campaign draft saved.');
    }

    public function show(NewsletterCampaign $newsletterCampaign): View
    {
        $newsletterCampaign->load('creator:id,name');

        $subscribers = NewsletterSubscriber::query()
            ->orderBy('full_name')
            ->orderBy('email')
            ->get(['id', 'full_name', 'email']);

        return view('admin.newsletter-campaigns.show', [
            'campaign' => $newsletterCampaign,
            'subscribers' => $subscribers,
            'subscriberCount' => $subscribers->count(),
        ]);
    }

    public function edit(NewsletterCampaign $newsletterCampaign): View
    {
        abort_unless($newsletterCampaign->isDraft(), 404);

        return view('admin.newsletter-campaigns.edit', [
            'campaign' => $newsletterCampaign,
            'subscriberCount' => NewsletterSubscriber::count(),
        ]);
    }

    public function update(Request $request, NewsletterCampaign $newsletterCampaign): RedirectResponse
    {
        abort_unless($newsletterCampaign->isDraft(), 404);

        $data = $this->validatePayload($request);
        $data['images'] = $this->storeUploadedImages(
            $request,
            $newsletterCampaign->imagePaths(),
        );

        $newsletterCampaign->update($data);

        return redirect()
            ->route('admin.newsletter-campaigns.show', $newsletterCampaign)
            ->with('status', 'Campaign updated.');
    }

    public function destroy(NewsletterCampaign $newsletterCampaign): RedirectResponse
    {
        abort_unless($newsletterCampaign->isDraft() || $newsletterCampaign->status === 'failed', 404);

        $newsletterCampaign->deleteStoredImages();
        $newsletterCampaign->delete();

        return redirect()
            ->route('admin.newsletter-campaigns.index')
            ->with('status', 'Campaign removed.');
    }

    public function send(Request $request, NewsletterCampaign $newsletterCampaign): RedirectResponse
    {
        abort_unless($newsletterCampaign->isDraft() || $newsletterCampaign->status === 'failed', 404);

        $validated = $request->validate([
            'recipient_mode' => ['required', Rule::in(NewsletterCampaign::RECIPIENT_MODES)],
            'subscriber_ids' => ['nullable', 'array'],
            'subscriber_ids.*' => ['integer', 'exists:newsletter_subscribers,id'],
        ]);

        $mode = $validated['recipient_mode'];
        $selectedIds = array_values(array_unique(array_map('intval', $validated['subscriber_ids'] ?? [])));

        if ($mode === 'selected') {
            if ($selectedIds === []) {
                throw ValidationException::withMessages([
                    'subscriber_ids' => 'Select at least one subscriber, or choose “All subscribers”.',
                ]);
            }

            $subscribers = NewsletterSubscriber::query()
                ->whereIn('id', $selectedIds)
                ->orderBy('id')
                ->get();
        } else {
            $subscribers = NewsletterSubscriber::query()->orderBy('id')->get();
            $selectedIds = $subscribers->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($subscribers->isEmpty()) {
            return redirect()
                ->route('admin.newsletter-campaigns.show', $newsletterCampaign)
                ->withErrors(['send' => 'No subscribers to send to.']);
        }

        $newsletterCampaign->update([
            'status' => 'sending',
            'recipient_mode' => $mode,
            'recipient_ids' => $selectedIds,
            'recipients_count' => $subscribers->count(),
            'sent_count' => 0,
            'last_error' => null,
        ]);

        try {
            ZeptoMailSettings::applyRuntimeConfig();
            $mailer = ZeptoMailSettings::isConfigured()
                ? 'zeptomail'
                : (string) config('mail.default', 'log');

            $sent = 0;
            foreach ($subscribers as $subscriber) {
                Mail::mailer($mailer)
                    ->to($subscriber->email, $subscriber->full_name)
                    ->send(new NewsletterCampaignMail($newsletterCampaign, (string) ($subscriber->full_name ?? '')));
                $sent++;
            }

            $newsletterCampaign->update([
                'status' => 'sent',
                'sent_count' => $sent,
                'sent_at' => now(),
                'last_error' => null,
            ]);

            return redirect()
                ->route('admin.newsletter-campaigns.show', $newsletterCampaign)
                ->with('status', "Campaign sent to {$sent} subscriber(s).");
        } catch (\Throwable $e) {
            report($e);
            $newsletterCampaign->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.newsletter-campaigns.show', $newsletterCampaign)
                ->withErrors(['send' => 'Sending failed: '.$e->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:100000'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string', 'max:255'],
        ]);

        unset($data['images'], $data['remove_images']);

        return $data;
    }

    /**
     * @param  list<string>  $existing
     * @return list<string>
     */
    private function storeUploadedImages(Request $request, array $existing): array
    {
        $keep = $existing;

        $remove = array_map('strval', $request->input('remove_images', []));
        if ($remove !== []) {
            foreach ($remove as $path) {
                if (in_array($path, $keep, true)) {
                    MediaStorage::delete($path);
                    $keep = array_values(array_filter($keep, fn (string $p) => $p !== $path));
                }
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if (! $file) {
                    continue;
                }
                $keep[] = MediaStorage::store($file, 'newsletter-campaigns');
            }
        }

        return array_values(array_slice($keep, 0, 8));
    }
}
