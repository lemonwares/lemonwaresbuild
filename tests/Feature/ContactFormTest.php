<?php

namespace Tests\Feature;

use App\Mail\ContactFormAcknowledgement;
use App\Mail\ContactFormSubmitted;
use App\Models\IntegrationSetting;
use App\Support\ContactFormSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_renders_form(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee(__('pages.contact.form_title'), false)
            ->assertSee(route('contact.store'), false);
    }

    public function test_contact_page_prefills_subject_from_query(): void
    {
        $this->get(route('contact', ['subject' => 'Plan a WordPress site']))
            ->assertOk()
            ->assertSee('value="Plan a WordPress site"', false);
    }

    public function test_contact_form_sends_team_and_acknowledgement_emails(): void
    {
        Mail::fake();

        config([
            'site.contact_form_to' => 'hello@lemonwares.com',
        ]);

        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'full_name' => 'Francis Uzoigwe',
                'email' => 'francis@example.com',
                'subject' => 'Hosting quote',
                'message' => 'I need a VPS for a Laravel app.',
            ])
            ->assertRedirect(route('contact').'#contact-form')
            ->assertSessionHas('contact_feedback.type', 'success');

        Mail::assertSent(ContactFormSubmitted::class, function (ContactFormSubmitted $mail): bool {
            return $mail->hasTo('hello@lemonwares.com')
                && $mail->fullName === 'Francis Uzoigwe'
                && $mail->email === 'francis@example.com'
                && $mail->subjectLine === 'Hosting quote';
        });

        Mail::assertSent(ContactFormAcknowledgement::class, function (ContactFormAcknowledgement $mail): bool {
            return $mail->hasTo('francis@example.com')
                && $mail->fullName === 'Francis Uzoigwe';
        });
    }

    public function test_contact_form_validates_required_fields(): void
    {
        Mail::fake();

        $this->from(route('contact'))
            ->post(route('contact.store'), [])
            ->assertSessionHasErrors(['full_name', 'email', 'subject', 'message']);

        Mail::assertNothingSent();
    }

    public function test_honeypot_submission_is_silently_accepted(): void
    {
        Mail::fake();

        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'full_name' => 'Bot User',
                'email' => 'bot@example.com',
                'subject' => 'Spam',
                'message' => 'Buy now',
                'company' => 'Acme Inc',
            ])
            ->assertRedirect(route('contact').'#contact-form')
            ->assertSessionHas('contact_feedback.type', 'success');

        Mail::assertNothingSent();
    }

    public function test_contact_form_uses_admin_inbox_when_configured(): void
    {
        Mail::fake();

        IntegrationSetting::putMany([
            'contact_form.inbox' => 'sales@lemonwares.com',
        ]);

        $this->assertSame('sales@lemonwares.com', ContactFormSettings::inboxAddress());

        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'full_name' => 'Francis Uzoigwe',
                'email' => 'francis@example.com',
                'subject' => 'Hosting quote',
                'message' => 'Need shared hosting.',
            ])
            ->assertRedirect(route('contact').'#contact-form');

        Mail::assertSent(ContactFormSubmitted::class, fn (ContactFormSubmitted $mail): bool => $mail->hasTo('sales@lemonwares.com'));
    }

    public function test_submitted_mail_uses_reply_to_address(): void
    {
        config([
            'services.zeptomail.from_address' => 'noreply@lemonwares.com',
            'services.zeptomail.from_name' => 'LemonWares',
        ]);

        $mail = new ContactFormSubmitted(
            fullName: 'Ada Lovelace',
            email: 'ada@example.com',
            subjectLine: 'Business email',
            body: 'Need Lemon Mail for 5 users.',
        );

        $envelope = $mail->envelope();

        $this->assertSame('noreply@lemonwares.com', $envelope->from->address);
        $this->assertSame('ada@example.com', $envelope->replyTo[0]->address);
        $this->assertSame('Ada Lovelace', $envelope->replyTo[0]->name);
    }
}
