<?php

namespace Tests\Feature;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsletterCampaignExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_campaign_can_upload_images_and_send_to_selected_subscribers(): void
    {
        Storage::fake('public');
        Mail::fake();
        $this->loginAsAdmin();

        NewsletterSubscriber::query()->delete();
        $ada = NewsletterSubscriber::query()->create([
            'full_name' => 'Ada',
            'email' => 'ada@example.com',
        ]);
        $bob = NewsletterSubscriber::query()->create([
            'full_name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        $this->post(route('admin.newsletter-campaigns.store'), [
            'subject' => 'Selected send',
            'body' => 'Hello selected friends',
            'images' => [
                UploadedFile::fake()->create('hero.jpg', 120, 'image/jpeg'),
            ],
        ])->assertRedirect();

        $campaign = NewsletterCampaign::query()->firstOrFail();
        $this->assertCount(1, $campaign->imagePaths());
        Storage::disk('public')->assertExists($campaign->imagePaths()[0]);

        $this->post(route('admin.newsletter-campaigns.send', $campaign), [
            'recipient_mode' => 'selected',
            'subscriber_ids' => [$ada->id],
        ])->assertRedirect(route('admin.newsletter-campaigns.show', $campaign));

        Mail::assertSent(NewsletterCampaignMail::class, 1);
        Mail::assertSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('ada@example.com'));
        Mail::assertNotSent(NewsletterCampaignMail::class, fn (NewsletterCampaignMail $mail) => $mail->hasTo('bob@example.com'));

        $campaign->refresh();
        $this->assertSame('selected', $campaign->recipient_mode);
        $this->assertSame([$ada->id], $campaign->recipient_ids);
        $this->assertSame(1, $campaign->sent_count);
    }
}
