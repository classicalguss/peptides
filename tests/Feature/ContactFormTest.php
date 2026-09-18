<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.contact.address' => 'orders@example.com']);
    }

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'topic' => 'order',
            'order_reference' => '00000012',
            'message' => 'My parcel has not arrived yet, could you check the tracking?',
        ], $overrides);
    }

    public function test_a_submission_is_saved_and_forwarded_to_the_store_inbox(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status');

        $message = ContactMessage::query()->sole();
        $this->assertSame('ada@example.com', $message->email);

        Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail) use ($message) {
            return $mail->hasTo('orders@example.com')
                && $mail->hasReplyTo('ada@example.com')
                && $mail->message->is($message);
        });
    }

    public function test_the_forwarded_email_carries_the_message_details(): void
    {
        $message = ContactMessage::create($this->payload());

        $mail = new ContactMessageReceived($message);
        $html = $mail->render();

        $this->assertSame('[Contact] An existing order — Ada Lovelace (order 00000012)', $mail->envelope()->subject);
        $this->assertStringContainsString('ada@example.com', $html);
        $this->assertStringContainsString('An existing order', $html);
        $this->assertStringContainsString('00000012', $html);
        $this->assertStringContainsString('My parcel has not arrived yet', $html);
    }

    public function test_the_subject_omits_the_order_reference_when_none_was_given(): void
    {
        $message = ContactMessage::create($this->payload(['topic' => 'general', 'order_reference' => null]));

        $this->assertSame('[Contact] Something else — Ada Lovelace', (new ContactMessageReceived($message))->envelope()->subject);
    }

    public function test_a_mail_failure_still_saves_the_message_and_thanks_the_customer(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('mailgun is down'));

        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status');

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_the_honeypot_blocks_bots_without_saving_or_sending(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->payload(['website' => 'http://spam.example']))
            ->assertRedirect(route('contact'));

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_invalid_input_is_rejected_without_sending(): void
    {
        Mail::fake();

        $this->from(route('contact'))
            ->post(route('contact.store'), $this->payload(['email' => 'not-an-email', 'message' => 'short']))
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['email', 'message']);

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }
}
