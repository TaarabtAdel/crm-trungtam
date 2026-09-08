<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\Lead;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\CurrentBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * Select2 AJAX — học viên (phân trang).
     */
    public function students(Request $request): JsonResponse
    {
        $term = $this->term($request);
        $page = $this->page($request);

        $paginator = CurrentBranch::apply(Student::query())
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('parent_name', 'like', "%{$term}%")
                        ->orWhere('parent_phone', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(20, ['id', 'name', 'phone', 'parent_phone', 'status'], 'page', $page);

        return $this->select2($paginator, function (Student $s) {
            $phone = $s->phone ?: $s->parent_phone;
            $text = $s->name;
            if ($phone) {
                $text .= ' · '.$phone;
            }
            if ($s->status && $s->status !== 'studying') {
                $text .= ' ('.$s->statusLabel().')';
            }

            return ['id' => $s->id, 'text' => $text];
        });
    }

    /**
     * Select2 AJAX — lead (phân trang).
     */
    public function leads(Request $request): JsonResponse
    {
        $term = $this->term($request);
        $page = $this->page($request);
        $user = $request->user();

        $query = CurrentBranch::apply(Lead::query())
            ->when(! $request->boolean('include_closed'), fn ($q) => $q->whereNotIn('status', ['won', 'lost']))
            ->when($user->isSales(), fn ($q) => $q->where('assigned_sales_id', $user->id))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name');

        $paginator = $query->paginate(20, ['id', 'name', 'phone', 'status'], 'page', $page);

        return $this->select2($paginator, function (Lead $lead) {
            $text = $lead->name;
            if ($lead->phone) {
                $text .= ' — '.$lead->phone;
            }

            return ['id' => $lead->id, 'text' => $text];
        });
    }

    /**
     * Select2 AJAX — lớp học (phân trang).
     */
    public function classes(Request $request): JsonResponse
    {
        $term = $this->term($request);
        $page = $this->page($request);
        $activeOnly = $request->boolean('active_only');

        $paginator = CurrentBranch::apply(CourseClass::query())
            ->when($activeOnly, fn ($q) => $q->where('status', 'active'))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('room', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(20, ['id', 'name', 'code', 'status', 'tuition_fee', 'tuition_type'], 'page', $page);

        return $this->select2($paginator, function (CourseClass $c) {
            $text = $c->name;
            if ($c->code) {
                $text .= ' ('.$c->code.')';
            }
            if (method_exists($c, 'tuitionDisplay')) {
                $text .= ' · '.$c->tuitionDisplay();
            }

            return [
                'id' => $c->id,
                'text' => $text,
                'fee' => (float) $c->tuition_fee,
                'type' => $c->isPerSessionFee() ? 'per_session' : 'monthly',
            ];
        });
    }

    /**
     * Select2 AJAX — giáo viên (phân trang).
     */
    public function teachers(Request $request): JsonResponse
    {
        $term = $this->term($request);
        $page = $this->page($request);

        $paginator = CurrentBranch::apply(Teacher::query())
            ->when($request->boolean('active_only', true), fn ($q) => $q->where('status', 'active'))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(20, ['id', 'name', 'phone', 'status'], 'page', $page);

        return $this->select2($paginator, function (Teacher $t) {
            $text = $t->name;
            if ($t->phone) {
                $text .= ' — '.$t->phone;
            }

            return ['id' => $t->id, 'text' => $text];
        });
    }

    protected function term(Request $request): string
    {
        return trim((string) $request->get('q', $request->get('term', '')));
    }

    protected function page(Request $request): int
    {
        return max(1, (int) $request->get('page', 1));
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @param  callable(mixed): array{id: int|string, text: string}  $map
     */
    protected function select2($paginator, callable $map): JsonResponse
    {
        return response()->json([
            'results' => $paginator->getCollection()->map($map)->values(),
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
        ]);
    }
}
