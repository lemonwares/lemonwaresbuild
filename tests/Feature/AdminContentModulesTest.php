<?php

namespace Tests\Feature;

use App\Mail\NewsletterCampaignMail;
use App\Mail\SupportTicketReply;
use App\Models\BlogPost;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminContentModulesTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        return User::query()->where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_admin_can_manage_staff_blog_projects_and_campaigns(): void
    {
        Mail::fake();
        $admin = $this->loginAsAdmin();

        $this->post(route('admin.staff.store'), [
            'name' => 'Ops Lead',
            'email' => 'ops@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_super_admin' => '1',
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'ops@example.com',
            'role' => 'admin',
        ]);

        $this->post(route('admin.blog-posts.store'), [
            'title' => 'Launch Notes',
            'excerpt' => 'A short excerpt',
            'body' => "First paragraph.\n\nSecond paragraph.",
            'is_published' => '1',
            'sort_order' => 1,
        ])->assertRedirect(route('admin.blog-posts.index'));

        $post = BlogPost::query()->where('slug', 'launch-notes')->firstOrFail();
        $this->assertTrue($post->is_published);
        $this->assertSame($admin->id, $post->author_id);

        $this->get(route('blog'))
            ->assertOk()
            ->assertSee('Launch Notes', false);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('First paragraph.', false);

        $this->post(route('admin.projects.store'), [
            'title' => 'Bright Media Rebuild',
            'client_name' => 'Bright Media',
            'summary' => 'Full site rebuild',
            'description' => "We rebuilt their stack.\n\nThen we launched.",
            'is_published' => '1',
            'sort_order' => 1,
        ])->assertRedirect(route('admin.projects.index'));

        $project = Project::query()->where('slug', 'bright-media-rebuild')->firstOrFail();

        $this->get(route('projects'))
            ->assertOk()
            ->assertSee('Bright Media Rebuild', false);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('We rebuilt their stack.', false);

        NewsletterSubscriber::query()->delete();
        NewsletterSubscriber::query()->create([
            'full_name' => 'Ada',
            'email' => 'ada@example.com',
        ]);

        $this->post(route('admin.newsletter-campaigns.store'), [
            'subject' => 'March update',
            'body' => 'Hello from LemonWares',
        ])->assertRedirect();

        $campaign = NewsletterCampaign::query()->firstOrFail();
        $this->assertSame('draft', $campaign->status);

        $this->post(route('admin.newsletter-campaigns.send', $campaign), [
            'recipient_mode' => 'all',
        ])
            ->assertRedirect(route('admin.newsletter-campaigns.show', $campaign));

        Mail::assertSent(NewsletterCampaignMail::class, function (NewsletterCampaignMail $mail): bool {
            return $mail->hasTo('ada@example.com')
                && $mail->campaign->subject === 'March update';
        });

        $campaign->refresh();
        $this->assertSame('sent', $campaign->status);
        $this->assertSame(1, $campaign->sent_count);
    }

    public function test_admin_can_reply_to_support_ticket_thread(): void
    {
        Mail::fake();
        $this->loginAsAdmin();

        $ticket = SupportTicket::query()->create([
            'reference' => 'LW-TESTTICK',
            'full_name' => 'Jane Client',
            'email' => 'jane@example.com',
            'category' => 'email',
            'priority' => 'normal',
            'subject' => 'Mailbox issue',
            'message' => 'Cannot send mail',
            'status' => 'open',
        ]);

        $this->get(route('admin.support-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Cannot send mail', false);

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'author_type' => 'customer',
            'body' => 'Cannot send mail',
        ]);

        $this->post(route('admin.support-tickets.reply', $ticket), [
            'body' => 'Please check your SMTP settings.',
            'notify_customer' => '1',
        ])->assertRedirect(route('admin.support-tickets.show', $ticket));

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'author_type' => 'admin',
            'body' => 'Please check your SMTP settings.',
        ]);

        Mail::assertSent(SupportTicketReply::class, function (SupportTicketReply $mail) use ($ticket): bool {
            return $mail->hasTo($ticket->email)
                && $mail->ticket->is($ticket);
        });

        $ticket->refresh();
        $this->assertSame('in_progress', $ticket->status);
    }
}
