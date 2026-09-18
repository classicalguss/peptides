<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Forwards a storefront contact-form submission to the store's inbox.
 *
 * Reply-To is the customer, so answering from the mailbox replies to them
 * directly rather than to the storefront's sending address.
 */
class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $message) {}

    public function envelope(): Envelope
    {
        $subject = '[Contact] '.$this->message->topicLabel().' — '.$this->message->name;

        if ($this->message->order_reference) {
            $subject .= ' (order '.$this->message->order_reference.')';
        }

        return new Envelope(
            subject: $subject,
            replyTo: [new Address($this->message->email, $this->message->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact.received',
            with: [
                'name' => $this->message->name,
                'email' => $this->message->email,
                'topic' => $this->message->topicLabel(),
                'orderReference' => $this->message->order_reference,
                'body' => $this->message->message,
                'receivedAt' => $this->message->created_at?->timezone(config('app.timezone'))->format('j M Y, H:i'),
            ],
        );
    }
}
