<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Lunar\Models\Order;

class AccountController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)
            ->with(['lines', 'addresses'])
            ->latest('id')
            ->get();

        return view('storefront.account.index', [
            'user' => $user,
            'orders' => $orders,
            'spend' => $orders->sum(fn (Order $order) => $order->total->value),
        ]);
    }

    public function order(string $reference): View
    {
        $order = Order::where('reference', $reference)
            ->where('user_id', Auth::id())
            ->with(['lines', 'addresses'])
            ->firstOrFail();

        return view('storefront.account.order', [
            'order' => $order,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->update($data);

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        Auth::user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('status', 'Password updated.');
    }
}
