<?php

namespace App\Providers;

use App\Listeners\ForgetUserNotificationCache;
use App\Models\CourseClass;
use App\Models\Task;
use App\Observers\TaskObserver;
use App\Support\CurrentBranch;
use App\Support\SmartCache;
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

        Route::bind('class', fn (string $value) => CourseClass::query()->findOrFail($value));

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
                'headerBranches' => SmartCache::activeBranches(),
                'currentBranchId' => CurrentBranch::id(),
                'headerNotifications' => $headerNotifications,
                'headerUnreadNotifications' => $headerUnreadNotifications,
            ]);
        });

        View::composer('*', function ($view) {
            $view->with('currentBranchId', CurrentBranch::id());
        });
    }
}
