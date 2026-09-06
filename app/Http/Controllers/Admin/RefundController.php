<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Finance\RefundService;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(protected RefundService $refunds) {}

    public function index(Request $request)
    {
        $status = $request->get('status');
        $items = Refund::with(['payment.invoice.student', 'requester', 'approver'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->whereHas('payment.invoice', function ($q) {
                CurrentBranch::apply($q);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.finance.refunds', compact('items', 'status'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string',
        ]);

        $payment = Payment::with('invoice')->findOrFail($data['payment_id']);
        $this->refunds->request($payment, $data, $request->user());

        return back()->with('success', 'Đã gửi yêu cầu hoàn tiền.');
    }

    public function approve(Request $request, Refund $refund)
    {
        $this->refunds->approve($refund, $request->user());

        return back()->with('success', 'Đã duyệt hoàn tiền.');
    }

    public function reject(Request $request, Refund $refund)
    {
        $this->refunds->reject($refund, $request->user());

        return back()->with('success', 'Đã từ chối hoàn tiền.');
    }
}
