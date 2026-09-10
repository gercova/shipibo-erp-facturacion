<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Maximum login attempts before the account is temporarily locked.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in seconds (1 minute).
     */
    private const DECAY_SECONDS = 60;

    /**
     * Maximum allowed length for the username field.
     */
    private const MAX_USER_LENGTH = 60;

    /**
     * Minimum required length for the password field.
     */
    private const MIN_PASSWORD_LENGTH = 6;

    /**
     * Maximum allowed length for the password field.
     */
    private const MAX_PASSWORD_LENGTH = 100;

    public function index(): View
    {
        $data['logo'] = Business::first()->logo;
        return view('login', $data);
    }

    public function login(Request $request): RedirectResponse|View
    {
        // ── 1. Input sanitisation & basic validation ───────────────────────
        $user     = trim((string) $request->input('user', ''));
        $password = (string) $request->input('password', '');

        if ($user === '' || $password === '') {
            return back()->with('message', 'El usuario y la contraseña son obligatorios.');
        }

        if (mb_strlen($user) > self::MAX_USER_LENGTH) {
            return back()->with('message', 'El nombre de usuario no es válido.');
        }

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return back()->with('message', 'La contraseña debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres.');
        }

        if (mb_strlen($password) > self::MAX_PASSWORD_LENGTH) {
            return back()->with('message', 'La contraseña proporcionada no es válida.');
        }

        // Allow only alphanumeric characters, dots, hyphens and underscores in the username.
        if (! preg_match('/^[\w.\-@]+$/u', $user)) {
            return back()->with('message', 'El nombre de usuario contiene caracteres no permitidos.');
        }

        // ── 2. Rate-limiter check (keyed by username + client IP) ──────────
        $throttleKey = $this->throttleKey($user, $request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with(
                'message',
                "Demasiados intentos fallidos. Por favor, espera {$seconds} segundo(s) antes de intentarlo de nuevo."
            );
        }

        // ── 3. Authentication attempt ──────────────────────────────────────
        $credentials = [
            'user'     => strtolower($user),
            'password' => $password,
        ];

        if (! Auth::attempt($credentials)) {
            // Register failed attempt and clear any authenticated session.
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            Auth::logout();

            $remaining = self::MAX_ATTEMPTS - RateLimiter::attempts($throttleKey);
            $message   = $remaining > 0
                ? "Estas credenciales no coinciden con nuestros registros. Intentos restantes: {$remaining}."
                : 'Cuenta bloqueada temporalmente por exceso de intentos fallidos.';

            return back()->with('message', $message);
        }

        // ── 4. Clear rate-limiter counter on successful authentication ──────
        RateLimiter::clear($throttleKey);

        // ── 5. Account status validation ───────────────────────────────────
        $authUser = User::query()->find(Auth::user()->id);

        if ((int) ($authUser->estado ?? 0) !== 1) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->with('message', 'No tiene los permisos necesarios para acceder al sistema.');
        }

        // ── 6. Session fixation protection ────────────────────────────────
        $request->session()->regenerate();

        // ── 7. Warehouse assignment ────────────────────────────────────────
        $warehouseIds = method_exists($authUser, 'warehouses')
            ? $authUser->warehouses()->pluck('warehouses.id')->map(fn ($id) => (int) $id)->filter()->values()
            : collect();

        if ($warehouseIds->isEmpty() && (int) ($authUser->idalmacen ?? 0) > 0) {
            $request->session()->put('selected_warehouse_id', (int) $authUser->idalmacen);
        } elseif ($warehouseIds->count() === 1) {
            $selectedWarehouseId = (int) $warehouseIds->first();
            $request->session()->put('selected_warehouse_id', $selectedWarehouseId);

            if ((int) $authUser->idalmacen !== $selectedWarehouseId) {
                $authUser->forceFill(['idalmacen' => $selectedWarehouseId])->saveQuietly();
            }
        } elseif ($warehouseIds->count() > 1) {
            $request->session()->forget('selected_warehouse_id');

            return redirect()->route('warehouse.selector.index');
        }

        return redirect()->route('admin.home')->with('message_welcome', 'Bienvenido al sistema.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Build a unique rate-limiter key combining the (normalised) username
     * and the client's IP address to prevent cross-account collisions.
     */
    private function throttleKey(string $user, Request $request): string
    {
        return 'login|' . Str::lower($user) . '|' . $request->ip();
    }
}
