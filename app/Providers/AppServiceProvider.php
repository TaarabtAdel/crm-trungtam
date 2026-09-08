<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\CourseClass;
use App\Support\CurrentBranch;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
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
        Paginator::useBootstrap();

        Route::bind('class', fn (string $value) => CourseClass::query()->findOrFail($value));

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
                $headerNotifications = $user->notifications()->latest()->limit(12)->get();
                $headerUnreadNotifications = $user->unreadNotifications()->count();
            }

            $view->with([
                'headerBranches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
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
