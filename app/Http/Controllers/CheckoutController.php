<?php

namespace App\Http\Controllers;

use App\Listeners\SendOrderConfirmation;
use App\Payments\VerifiedCryptoClient;
use App\Payments\VerifiedCryptoException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Lunar\Facades\CartSession;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Order;

class CheckoutController extends Controller
{
    public function begin(Request $request): RedirectResponse
    {
        $cart = CartSession::current();

        if (! $cart || $cart->lines->isEmpty()) {
            return redirect()->route('cart');
        }

        $request->validate([
            'research_disclaimer_accepted' => ['accepted'],
        ], [
            'research_disclaimer_accepted.accepted' => 'You must accept the research-use disclaimer before proceeding to checkout.',
        ]);

        $request->session()->put('research_disclaimer_accepted', true);

        return redirect()->route('checkout');
    }

    public function show(): View|RedirectResponse
    {
        $cart = CartSession::current();

        if (! $cart || $cart->lines->isEmpty()) {
            return redirect()->route('cart');
        }

        if (! session('research_disclaimer_accepted')) {
            return redirect()
                ->route('cart')
                ->withErrors(['research_disclaimer_accepted' => 'You must accept the research-use disclaimer before proceeding to checkout.']);
        }

        $cart->calculate();

        return view('storefront.checkout', [
            'cart' => $cart,
            'shippingOptions' => ShippingManifest::getOptions($cart),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cart = CartSession::current();

        if (! $cart || $cart->lines->isEmpty()) {
            return redirect()->route('cart');
        }

        if (! $request->session()->get('research_disclaimer_accepted')) {
            return redirect()
                ->route('cart')
                ->withErrors(['research_disclaimer_accepted' => 'You must accept the research-use disclaimer before proceeding to checkout.']);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'line_one' => ['required', 'string', 'max:255'],
            'line_two' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:32'],
            'country_id' => ['required', 'integer', 'exists:lunar_countries,id'],
            'shipping_option' => ['required', 'string'],
            'research_use_confirmed' => ['accepted'],
        ], [
            'research_use_confirmed.accepted' => 'You must confirm these products are for research use only.',
        ]);

        $address = collect($data)->only([
            'first_name', 'last_name', 'company_name', 'line_one', 'line_two',
            'city', 'state', 'postcode', 'country_id', 'contact_phone',
        ])->put('contact_email', $data['email'])->all();

        $shippingOption = ShippingManifest::getOption($cart, $data['shipping_option']);

        if (! $shippingOption) {
            return back()
                ->withInput()
                ->withErrors(['shipping_option' => 'That shipping method is no longer available.']);
        }

        $cart->setShippingAddress($address);
        $cart->setBillingAddress($address);
        $cart->setShippingOption($shippingOption);
        $cart->calculate();

        // Card-to-crypto providers reject small amounts, so an order below the
        // minimum could never be paid. Reject it here rather than creating an
        // order that would strand at awaiting-payment.
        if (config('verified-crypto.enabled')) {
            $minimum = (int) config('verified-crypto.minimum_order');

            if ($minimum > 0 && ($cart->total?->value ?? 0) < $minimum) {
                return back()
                    ->withInput()
                    ->withErrors(['payment' => 'The minimum order total for checkout is $'.number_format($minimum / 100, 2).'. Please add more items to your cart before checking out.']);
            }
        }

        $order = DB::transaction(fn () => $cart->createOrder());

        if (config('verified-crypto.enabled')) {
            return $this->redirectToVerifiedCheckout($request, $order, $data['email']);
        }

        CartSession::forget();
        $request->session()->forget('research_disclaimer_accepted');

        // Offline payment never produces a payment attempt, so the receipt is
        // sent here. Card-to-crypto orders are emailed by the settlement
        // callback instead, once the payment has actually cleared.
        app(SendOrderConfirmation::class)->send($order);

        return redirect()->route('checkout.confirmation', $order->reference);
    }

    /**
     * Hand the customer off to the VERIFIED-hosted checkout page.
     *
     * The order stays at the draft status until a relay callback confirms
     * settlement on-chain, so nothing here marks it paid. If the session
     * cannot be created the order is left in place as a record of the attempt
     * and the customer is returned to checkout with their input intact.
     */
    protected function redirectToVerifiedCheckout(Request $request, Order $order, string $email): RedirectResponse
    {
        try {
            $session = app(VerifiedCryptoClient::class)->createSession(
                $order,
                $email,
                route('webhooks.verified-crypto'),
            );
        } catch (VerifiedCryptoException $e) {
            Log::error('Could not start a VERIFIED payment session.', [
                'order_reference' => $order->reference,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['payment' => 'We could not start the payment session. Please try again in a moment.']);
        }

        $order->update([
            'meta' => array_merge((array) $order->meta, [
                'verified_crypto' => $session->toMeta(),
            ]),
        ]);

        CartSession::forget();
        $request->session()->forget('research_disclaimer_accepted');

        // The checkout URL is opaque — VERIFIED builds provider routing into it,
        // so it is passed through exactly as returned.
        return redirect()->away($session->checkoutUrl);
    }

    public function confirmation(string $reference): View
    {
        $order = Order::where('reference', $reference)
            ->with(['lines', 'addresses'])
            ->firstOrFail();

        return view('storefront.confirmation', [
            'order' => $order,
            // Crypto settlement confirms on-chain a minute or two after the
            // customer is redirected back, so the order can still be awaiting
            // its callback when this page is first rendered.
            'awaitingPayment' => $order->placed_at === null,
        ]);
    }
}
