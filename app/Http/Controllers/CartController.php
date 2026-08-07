<?php

namespace App\Http\Controllers;

use App\Shipping\FlatRateShipping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Lunar\Facades\CartSession;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = CartSession::current();

        return view('storefront.cart', [
            'cart' => $cart?->calculate(),
            'freeShippingThreshold' => FlatRateShipping::FREE_SHIPPING_THRESHOLD,
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:lunar_product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $variant = ProductVariant::findOrFail($data['variant_id']);

        // Calling a cart method through the facade creates the cart if the
        // visitor does not have one yet.
        CartSession::add($variant, $data['quantity'] ?? 1);

        return redirect()
            ->route('cart')
            ->with('status', 'Added to your cart.');
    }

    public function updateLine(Request $request, CartLine $line): RedirectResponse
    {
        $this->authorizeLine($line);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        if ($data['quantity'] === 0) {
            CartSession::remove($line->id);

            return redirect()->route('cart')->with('status', 'Item removed.');
        }

        CartSession::updateLine($line->id, $data['quantity']);

        return redirect()->route('cart')->with('status', 'Cart updated.');
    }

    public function removeLine(CartLine $line): RedirectResponse
    {
        $this->authorizeLine($line);

        CartSession::remove($line->id);

        return redirect()->route('cart')->with('status', 'Item removed.');
    }

    public function clear(): RedirectResponse
    {
        CartSession::clear();

        return redirect()->route('cart')->with('status', 'Cart cleared.');
    }

    /**
     * Guards against tampering with a cart line that belongs to another session.
     */
    protected function authorizeLine(CartLine $line): void
    {
        abort_if($line->cart_id !== CartSession::current()?->id, 403);
    }
}
