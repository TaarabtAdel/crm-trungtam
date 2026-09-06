<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('code', 40)->nullable()->unique()->after('id');
            $table->foreignId('branch_id')->nullable()->after('class_id')->constrained('branches')->nullOnDelete();
            $table->foreignId('sales_id')->nullable()->after('branch_id')->constrained('users')->nullOnDelete();
            $table->decimal('paid_amount', 14, 0)->default(0)->after('amount');
            $table->decimal('remaining_amount', 14, 0)->default(0)->after('paid_amount');
            $table->unsignedTinyInteger('installment_count')->default(1)->after('fee_type');
        });

        // Backfill existing invoices
        DB::table('invoices')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $paid = $row->status === 'paid' ? (float) $row->amount : 0;
                $remaining = max(0, (float) $row->amount - $paid);
                $branchId = DB::table('students')->where('id', $row->student_id)->value('branch_id');
                $code = sprintf('HD-%s-%04d', now()->format('Ym'), $row->id);

                DB::table('invoices')->where('id', $row->id)->update([
                    'code' => $code,
                    'branch_id' => $branchId,
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'installment_count' => 1,
                ]);
            }
        });

        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->decimal('amount', 14, 0)->default(0);
            $table->decimal('paid_amount', 14, 0)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('unpaid'); // unpaid, partial, paid, overdue
            $table->timestamps();
            $table->unique(['invoice_id', 'sequence']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('payment_installments')->nullOnDelete();
            $table->decimal('amount', 14, 0);
            $table->string('method')->default('cash'); // cash, bank_transfer, card, e_wallet
            $table->dateTime('paid_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->decimal('amount', 14, 0);
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('category')->default('other'); // operations, salary, marketing, other
            $table->decimal('amount', 14, 0);
            $table->date('expense_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, approved, rejected, paid
            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope')->default('global'); // global, class
            $table->foreignId('class_id')->nullable()->constrained('classes')->cascadeOnDelete();
            $table->decimal('percent', 5, 2)->default(0);
            $table->decimal('tier_min_revenue', 14, 0)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('percent', 5, 2)->default(0);
            $table->decimal('amount', 14, 0)->default(0);
            $table->string('status')->default('unpaid'); // unpaid, paid
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['sales_id', 'invoice_id']);
        });

        Schema::create('debt_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('channel'); // email, notification, sms
            $table->dateTime('sent_at');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        // Seed default global commission rule 5%
        DB::table('commission_rules')->insert([
            'scope' => 'global',
            'class_id' => null,
            'percent' => 5,
            'tier_min_revenue' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_reminders');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_installments');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['code', 'paid_amount', 'remaining_amount', 'installment_count']);
        });
    }
};
