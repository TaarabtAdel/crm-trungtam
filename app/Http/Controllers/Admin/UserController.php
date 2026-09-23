<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $role = (string) $request->get('role', '');
        $status = (string) $request->get('status', '');

        $users = User::with(['branch', 'roleAssignments'])
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($role !== '' && array_key_exists($role, config('permissions.roles', [])), function ($query) use ($role) {
                $query->where(function ($inner) use ($role) {
                    $inner->where('role', $role)
                        ->orWhereHas('roleAssignments', fn ($r) => $r->where('role', $role));
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $branches = CurrentBranch::activeBranches();
        $roleOptions = config('permissions.roles', []);

        $base = User::query()->tap(fn ($query) => CurrentBranch::apply($query));
        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
            'teachers' => (clone $base)->where(function ($q) {
                $q->where('role', 'teacher')
                    ->orWhereHas('roleAssignments', fn ($r) => $r->where('role', 'teacher'));
            })->count(),
        ];

        return view('admin.system.users', compact(
            'users', 'branches', 'q', 'role', 'status', 'roleOptions', 'stats'
        ));
    }

    public function store(Request $request)
    {
        $this->normalizeBranchIdInput($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'roles' => 'required|array|min:1',
            'roles.*' => ['string', Rule::in(array_keys(config('permissions.roles', [])))],
            'phone' => 'nullable|string|max:30',
            'daily_rate' => 'nullable|numeric|min:0',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'is_active' => 'nullable|boolean',
        ]);

        $roles = array_values(array_unique($data['roles']));
        unset($data['roles']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['daily_rate'] = $data['daily_rate'] ?? 0;
        $data['password'] = Hash::make($data['password']);
        $data['role'] = in_array('super_admin', $roles, true) ? 'super_admin' : $roles[0];
        $data['branch_id'] = $this->resolvedBranchId($data['branch_id'] ?? null);

        $user = User::create($data);
        $user->syncRoles($roles);

        return back()->with('success', 'Đã thêm người dùng.');
    }

    public function show(Request $request, User $user)
    {
        CurrentBranch::authorize($user->branch_id);

        $tab = $request->get('tab', 'info');
        if (! in_array($tab, ['info', 'attendance', 'payroll'], true)) {
            $tab = 'info';
        }

        $user->load(['branch', 'roleAssignments']);
        $branches = CurrentBranch::activeBranches();

        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $attendances = collect();
        $payrollSummary = null;

        if ($tab === 'attendance' || $tab === 'payroll') {
            $from = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
            $to = $from->copy()->endOfMonth();
            $attendances = $user->staffAttendances()
                ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
                ->orderBy('work_date')
                ->get();
        }

        if ($tab === 'payroll') {
            $rate = (float) $user->daily_rate;
            $units = round($attendances->sum(fn ($a) => $a->dayUnits()), 2);
            $accrued = (float) $attendances->sum(fn ($a) => $a->payAmount($rate));
            $billingMonth = sprintf('%04d-%02d', $year, $month);
            $paid = (float) \App\Models\Expense::query()
                ->where('category', 'staff_salary')
                ->where('user_id', $user->id)
                ->where('billing_month', $billingMonth)
                ->whereIn('status', ['pending', 'approved', 'paid'])
                ->sum('amount');
            $payrollSummary = [
                'rate' => $rate,
                'days' => $units,
                'accrued' => $accrued,
                'paid' => $paid,
                'remaining' => max(0, $accrued - $paid),
                'billing_month' => $billingMonth,
            ];
        }

        return view('admin.system.user_show', compact(
            'user', 'tab', 'branches', 'month', 'year', 'attendances', 'payrollSummary'
        ));
    }

    public function update(Request $request, User $user)
    {
        CurrentBranch::authorize($user->branch_id);
        $this->normalizeBranchIdInput($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'roles' => 'required|array|min:1',
            'roles.*' => ['string', Rule::in(array_keys(config('permissions.roles', [])))],
            'phone' => 'nullable|string|max:30',
            'daily_rate' => 'nullable|numeric|min:0',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'is_active' => 'nullable|boolean',
        ]);

        $roles = array_values(array_unique($data['roles']));
        unset($data['roles']);
        $data['is_active'] = $request->boolean('is_active');
        $data['daily_rate'] = $data['daily_rate'] ?? 0;
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $data['role'] = in_array('super_admin', $roles, true) ? 'super_admin' : $roles[0];
        $data['branch_id'] = $this->resolvedBranchId($data['branch_id'] ?? null);

        $user->fill($data);
        // Gán tường minh để chắc chắn ghi null khi bỏ chọn chi nhánh
        $user->branch_id = $data['branch_id'];
        $user->save();
        $user->syncRoles($roles);

        if ($request->boolean('from_detail')) {
            return redirect()
                ->route('admin.users.show', ['user' => $user, 'tab' => 'info'])
                ->with('success', 'Đã cập nhật người dùng.');
        }

        return back()->with('success', 'Đã cập nhật người dùng.');
    }

    public function destroy(User $user)
    {
        CurrentBranch::authorize($user->branch_id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Không thể xóa tài khoản đang đăng nhập.');
        }
        $user->delete();

        return back()->with('success', 'Đã xóa người dùng.');
    }

    /**
     * Admin / super admin: đăng nhập với tư cách user khác.
     */
    public function impersonate(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin() || $actor->hasAnyRole('admin'), 403);
        CurrentBranch::authorize($user->branch_id);

        if ($request->session()->has('impersonator_id')) {
            return back()->with('error', 'Đang giả lập tài khoản khác. Hãy thoát trước.');
        }

        if ($user->id === $actor->id) {
            return back()->with('error', 'Không thể đăng nhập vào chính tài khoản đang dùng.');
        }

        if (! $user->is_active) {
            return back()->with('error', 'Tài khoản đang bị khóa.');
        }

        if ($user->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'Không thể đăng nhập vào tài khoản siêu quản trị.');
        }

        $request->session()->put('impersonator_id', $actor->id);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('success', 'Đang đăng nhập với tư cách '.$user->name.'.');
    }

    public function leaveImpersonation(Request $request)
    {
        $actorId = $request->session()->pull('impersonator_id');
        if (! $actorId) {
            return redirect()->route('home');
        }

        $actor = User::query()->find($actorId);
        if (! $actor || ! $actor->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Không khôi phục được tài khoản quản trị. Vui lòng đăng nhập lại.',
            ]);
        }

        Auth::login($actor);
        $request->session()->regenerate();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã trở lại tài khoản '.$actor->name.'.');
    }

    /**
     * Chuẩn hoá input rỗng → null trước validate (tránh exists fail với "").
     */
    protected function normalizeBranchIdInput(Request $request): void
    {
        $raw = $request->input('branch_id');
        $request->merge([
            'branch_id' => ($raw === null || $raw === '') ? null : $raw,
        ]);
    }

    /**
     * User bị khóa chi nhánh → luôn ghi branch của họ.
     * Ngược lại → tôn trọng form (null = bỏ gắn).
     */
    protected function resolvedBranchId(mixed $branchId): ?int
    {
        if ($forced = CurrentBranch::forcedId()) {
            return $forced;
        }

        return $branchId !== null && $branchId !== '' ? (int) $branchId : null;
    }
}
