<?php

namespace Tests\Feature;

use App\Mail\SupportTicketAcknowledgement;
use App\Mail\SupportTicketSubmitted;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_page_renders(): void
    {
        $this->get(route('support'))
            ->assertOk()
            ->assertSee(__('pages.support_page.form_title'), false)
            ->assertSee(route('support.ticket.store'), false);
    }

    public function test_support_ticket_is_stored_and_emailed(): void
    {
        Mail::fake();

        config([
            'site.contact_form_to' => 'hello@lemonwares.com',
        ]);

        $this->from(route('support'))
            ->post(route('support.ticket.store'), [
                'full_name' => 'Francis Uzoigwe',
                'email' => 'francis@example.com',
                'phone' => '+2348000000000',
                'category' => 'hosting',
                'priority' => 'high',
                'subject' => 'SSL not renewing',
                'message' => 'Our certificate expired overnight.',
            ])
            ->assertRedirect(route('support').'#support-ticket')
            ->assertSessionHas('support_feedback.type', 'success');

        $ticket = SupportTicket::query()->first();
        $this->assertNotNull($ticket);
        $this->assertSame('hosting', $ticket->category);
        $this->assertSame('open', $ticket->status);
        $this->assertStringStartsWith('LW-', $ticket->reference);

        Mail::assertSent(SupportTicketSubmitted::class);
        Mail::assertSent(SupportTicketAcknowledgement::class);
    }

    public function test_support_ticket_validates_required_fields(): void
    {
        Mail::fake();

        $this->from(route('support'))
            ->post(route('support.ticket.store'), [])
            ->assertSessionHasErrors(['full_name', 'email', 'category', 'priority', 'subject', 'message']);

        Mail::assertNothingSent();
    }
}
