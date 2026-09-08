<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user = User::query()->where('email', $request->input('email'))->first();
        if ($user && ! $user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Tài khoản đã bị khóa. Liên hệ quản trị viên.']);
        }

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Đã đặt lại mật khẩu. Bạn có thể đăng nhập bằng mật khẩu mới.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $this->statusMessage($status)]);
    }

    protected function statusMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
            Password::INVALID_USER => 'Không tìm thấy tài khoản với email này.',
            default => 'Không thể đặt lại mật khẩu. Vui lòng thử lại.',
        };
    }
}
