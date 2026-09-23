<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $brand = (string) \App\Models\Setting::get('center_name', \App\Models\Setting::get('logo_text', config('app.name')));
        $logoText = (string) \App\Models\Setting::get('logo_text', 'CRM');

        return view('auth.login', compact('brand', 'logoText'));
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Vui lòng nhập email hoặc số điện thoại.',
        ]);

        $login = trim($data['login']);
        $user = $this->findUserByLogin($login);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['login' => 'Email/số điện thoại hoặc mật khẩu không đúng.'])
                ->onlyInput('login');
        }

        if (! $user->is_active) {
            return back()
                ->withErrors(['login' => 'Tài khoản đã bị khóa.'])
                ->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget('impersonator_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function findUserByLogin(string $login): ?User
    {
        if ($this->looksLikeEmail($login)) {
            return User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($login)])
                ->first();
        }

        $candidates = $this->phoneCandidates($login);
        if ($candidates === []) {
            return null;
        }

        $normalized = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(phone,''), ' ', ''), '-', ''), '+', ''), '.', ''), '(', '')";
        // bỏ nốt ')'
        $normalized = "REPLACE({$normalized}, ')', '')";

        return User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereIn(DB::raw($normalized), $candidates)
            ->first();
    }

    protected function looksLikeEmail(string $login): bool
    {
        return str_contains($login, '@') || filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @return list<string>
     */
    protected function phoneCandidates(string $login): array
    {
        $digits = preg_replace('/\D+/', '', $login) ?? '';
        if (strlen($digits) < 8) {
            return [];
        }

        $out = [$digits];

        if (str_starts_with($digits, '84') && strlen($digits) >= 11) {
            $local = substr($digits, 2);
            $out[] = '0'.$local;
            $out[] = $local;
        } elseif (str_starts_with($digits, '0')) {
            $local = substr($digits, 1);
            $out[] = '84'.$local;
            $out[] = $local;
        } else {
            $out[] = '0'.$digits;
            $out[] = '84'.$digits;
        }

        return array_values(array_unique($out));
    }
}
