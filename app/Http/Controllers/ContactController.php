<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Throwable;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('storefront.contact', [
            'topics' => ContactMessage::TOPICS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Bots fill every field they see; a real person leaves this one empty.
        if (filled($request->input('website'))) {
            return redirect()->route('contact')->with('status', 'Thanks — your message is with the team.');
        }

        $key = 'contact:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withInput()
                ->withErrors(['message' => 'Too many messages sent. Please try again in a few minutes.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'topic' => ['required', 'string', 'in:'.implode(',', array_keys(ContactMessage::TOPICS))],
            'order_reference' => ['nullable', 'string', 'max:64'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        RateLimiter::hit($key, 600);

        $message = ContactMessage::create($data);

        // The message is already saved; a mail outage must not turn a
        // successful submission into an error for the customer.
        try {
            Mail::to(config('mail.contact.address'))->send(new ContactMessageReceived($message));
        } catch (Throwable $e) {
            Log::error('Could not forward a contact message to the store inbox.', [
                'contact_message_id' => $message->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('contact')
            ->with('status', 'Thanks — your message is with the team. We reply within one business day.');
    }
}
