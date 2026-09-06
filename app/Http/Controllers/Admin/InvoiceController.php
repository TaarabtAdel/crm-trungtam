<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Student;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $invoices = Invoice::with(['student', 'courseClass'])
            ->tap(fn ($query) => CurrentBranch::applyThrough($query, 'student'))
            ->when($q, function ($query) use ($q) {
                $query->whereHas('student', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $students = CurrentBranch::apply(Student::query())->orderBy('name')->get();
        $classes = CurrentBranch::apply(CourseClass::query())->orderBy('name')->get();

        return view('admin.finance.invoices', compact('invoices', 'students', 'classes', 'q', 'status'));
    }

    public function suggest(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'billing_month' => 'nullable|string|max:7',
        ]);

        $class = CourseClass::findOrFail($data['class_id']);
        $suggestion = $class->suggestInvoiceAmount($data['billing_month'] ?? null);

        return response()->json([
            'class_name' => $class->name,
            'tuition_type' => $class->tuition_type,
            'tuition_type_label' => $class->tuitionTypeLabel(),
            'tuition_display' => $class->tuitionDisplay(),
            ...$suggestion,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (($data['status'] ?? 'unpaid') === 'paid') {
            $data['paid_at'] = now();
        }
        Invoice::create($data);

        return back()->with('success', 'Đã tạo hóa đơn.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $this->validated($request, true);
        if ($data['status'] === 'paid' && $invoice->status !== 'paid') {
            $data['paid_at'] = now();
        }
        if ($data['status'] !== 'paid') {
            $data['paid_at'] = null;
        }
        $invoice->update($data);

        return back()->with('success', 'Đã cập nhật hóa đơn.');
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', 'Đã đánh dấu đã thu.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return back()->with('success', 'Đã xóa hóa đơn.');
    }

    protected function validated(Request $request, bool $requireStatus = false): array
    {
        $rules = [
            'student_id' => 'required|exists:students,id',
            'class_id' => 'nullable|exists:classes,id',
            'amount' => 'required|numeric|min:0',
            'billing_month' => 'nullable|string|max:7',
            'sessions_count' => 'nullable|integer|min:0',
            'fee_type' => 'nullable|in:monthly,per_session',
            'status' => ($requireStatus ? 'required' : 'nullable').'|in:unpaid,paid,cancelled',
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ];

        $data = $request->validate($rules);
        $data['status'] = $data['status'] ?? 'unpaid';

        if (! empty($data['class_id']) && empty($data['fee_type'])) {
            $class = CourseClass::find($data['class_id']);
            $data['fee_type'] = $class?->tuition_type;
        }

        return $data;
    }
}
