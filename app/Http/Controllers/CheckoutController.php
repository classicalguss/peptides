<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $order = DB::transaction(function () use ($cart, $address, $shippingOption) {
            $cart->setShippingAddress($address);
            $cart->setBillingAddress($address);
            $cart->setShippingOption($shippingOption);
            $cart->calculate();

            return $cart->createOrder();
        });

        CartSession::forget();
        $request->session()->forget('research_disclaimer_accepted');

        return redirect()->route('checkout.confirmation', $order->reference);
    }

    public function confirmation(string $reference): View
    {
        $order = Order::where('reference', $reference)
            ->with(['lines', 'addresses'])
            ->firstOrFail();

        return view('storefront.confirmation', [
            'order' => $order,
        ]);
    }
}
