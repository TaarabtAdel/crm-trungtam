<?php

namespace App\Providers;

use App\Listeners\ForgetUserNotificationCache;
use App\Models\CourseClass;
use App\Models\Expense;
use App\Models\Interaction;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Task;
use App\Models\Teacher;
use App\Models\User;
use App\Observers\TaskObserver;
use App\Support\CurrentBranch;
use App\Support\SmartCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // MySQL/MariaDB cũ (cPanel): index utf8mb4 tối đa ~1000 bytes → varchar(255) PK/unique bị lỗi 1071.
        Schema::defaultStringLength(191);

        Paginator::useBootstrap();

        $this->bindBranchScopedRoutes();

        Task::observe(TaskObserver::class);

        Event::listen(NotificationSent::class, ForgetUserNotificationCache::class);

        Blade::directive('vnd', function ($expression) {
            return "<?php echo number_format((float) ({$expression}), 0, ',', '.') . ' đ'; ?>";
        });

        Blade::if('canPerm', function (string ...$permissions) {
            $user = auth()->user();
            if (! $user) {
                return false;
            }

            return $user->hasAnyPermission(...$permissions);
        });

        View::composer('partials.header', function ($view) {
            $user = auth()->user();
            $headerNotifications = collect();
            $headerUnreadNotifications = 0;

            if ($user) {
                $inbox = SmartCache::headerNotifications($user);
                $headerNotifications = $inbox['items'];
                $headerUnreadNotifications = $inbox['unread'];
            }

            $view->with([
                'headerBranches' => CurrentBranch::activeBranches(),
                'currentBranchId' => CurrentBranch::id(),
                'canSwitchBranch' => CurrentBranch::canSwitch(),
                'headerNotifications' => $headerNotifications,
                'headerUnreadNotifications' => $headerUnreadNotifications,
            ]);
        });

        View::composer('*', function ($view) {
            $view->with('currentBranchId', CurrentBranch::id());
        });
    }

    /**
     * Route model binding: record ngoài chi nhánh hiện tại → 404.
     */
    protected function bindBranchScopedRoutes(): void
    {
        $bindings = [
            'class' => CourseClass::class,
            'lead' => Lead::class,
            'student' => Student::class,
            'teacher' => Teacher::class,
            'subject' => Subject::class,
            'invoice' => Invoice::class,
            'expense' => Expense::class,
            'interaction' => Interaction::class,
            'task' => Task::class,
        ];

        foreach ($bindings as $param => $model) {
            Route::bind($param, function (string $value) use ($model) {
                /** @var Builder $q */
                $q = $model::query()->whereKey($value);
                CurrentBranch::apply($q);

                return $q->firstOrFail();
            });
        }

        // User: chỉ khóa theo branch bắt buộc trên hồ sơ (forced).
        // Không áp filter session — để bỏ gắn chi nhánh vẫn mở được hồ sơ.
        Route::bind('user', function (string $value) {
            $q = User::query()->whereKey($value);
            if ($forced = CurrentBranch::forcedId()) {
                $q->where('branch_id', $forced);
            }

            return $q->firstOrFail();
        });

        Route::bind('branch', function (string $value) {
            $q = \App\Models\Branch::query()->whereKey($value);
            if ($forced = CurrentBranch::forcedId()) {
                $q->whereKey($forced);
            }

            return $q->firstOrFail();
        });
    }
}
