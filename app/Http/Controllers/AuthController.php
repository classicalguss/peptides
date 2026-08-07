<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Lunar\Facades\CartSession;
use Lunar\Models\Customer;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('storefront.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Those credentials do not match our records.']);
        }

        $request->session()->regenerate();

        $this->associateCart();

        return redirect()->intended(route('account'));
    }

    public function showRegister(): View
    {
        return view('storefront.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'research_use_confirmed' => ['accepted'],
        ], [
            'research_use_confirmed.accepted' => 'You must confirm you are a qualified researcher.',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => "{$data['first_name']} {$data['last_name']}",
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $customer = Customer::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ]);

            $customer->users()->attach($user->id);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $this->associateCart();

        return redirect()->route('account');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Attaches a guest cart to the user who just signed in so nothing is lost.
     */
    protected function associateCart(): void
    {
        $cart = CartSession::current();
        $user = Auth::user();

        if ($cart && $user) {
            $cart->associate($user, 'merge');
        }
    }
}
