<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class ForgotPasswordController extends Controller
{
    /** Số giây chờ giữa 2 lần gửi link cho cùng email. */
    protected int $emailDecaySeconds = 60;

    /** Giới hạn số lần gửi theo IP trong cửa sổ thời gian. */
    protected int $ipMaxAttempts = 5;

    protected int $ipDecaySeconds = 600;

    public function showLinkRequestForm(Request $request)
    {
        $retryAfter = session('retry_after');
        $email = old('email');
        if ((! $retryAfter || $retryAfter < 1) && is_string($email) && $email !== '') {
            $retryAfter = RateLimiter::tooManyAttempts($this->emailKey($email), 1)
                ? $this->secondsUntilAvailable($this->emailKey($email))
                : null;
        }

        return view('auth.forgot-password', [
            'retryAfter' => $retryAfter ? (int) $retryAfter : null,
        ]);
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $emailKey = $this->emailKey($email);
        $ipKey = $this->ipKey($request);

        if (RateLimiter::tooManyAttempts($emailKey, 1)) {
            $seconds = $this->secondsUntilAvailable($emailKey);

            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'email' => "Vui lòng chờ {$seconds} giây trước khi gửi lại yêu cầu đặt lại mật khẩu.",
                ])
                ->with('retry_after', $seconds);
        }

        if (RateLimiter::tooManyAttempts($ipKey, $this->ipMaxAttempts)) {
            $seconds = $this->secondsUntilAvailable($ipKey);

            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'email' => "Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau {$seconds} giây.",
                ])
                ->with('retry_after', $seconds);
        }

        // Hit trước khi gửi — kể cả email không tồn tại (chống dò email / spam)
        RateLimiter::hit($emailKey, $this->emailDecaySeconds);
        RateLimiter::hit($ipKey, $this->ipDecaySeconds);

        $user = User::query()->where('email', $email)->first();

        if ($user && $user->is_active) {
            $status = Password::broker()->sendResetLink(['email' => $email]);

            if ($status === Password::RESET_THROTTLED) {
                return back()
                    ->withInput(['email' => $email])
                    ->withErrors([
                        'email' => "Vui lòng chờ {$this->emailDecaySeconds} giây trước khi gửi lại yêu cầu đặt lại mật khẩu.",
                    ])
                    ->with('retry_after', $this->emailDecaySeconds);
            }
        }

        return back()
            ->withInput(['email' => $email])
            ->with('status', 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu. Vui lòng kiểm tra hộp thư (và Spam).')
            ->with('retry_after', $this->emailDecaySeconds);
    }

    protected function emailKey(string $email): string
    {
        return 'password-reset:email:'.sha1($email);
    }

    protected function ipKey(Request $request): string
    {
        return 'password-reset:ip:'.$request->ip();
    }

    protected function secondsUntilAvailable(string $key): int
    {
        return max(1, RateLimiter::availableIn($key));
    }
}
