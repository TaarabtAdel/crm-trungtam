<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadAssignmentNotifier;
use App\Support\CurrentBranch;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadExcelImporter
{
    public const HEADERS = [
        'Họ tên *',
        'SĐT',
        'Email',
        'Người thân - Họ tên',
        'Người thân - SĐT',
        'Người thân - Email',
        'Nguồn',
        'Chi nhánh',
        'Doanh thu dự kiến',
        'Sales (email)',
        'Trạng thái',
        'Hạn xử lý (YYYY-MM-DD)',
    ];

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Leads');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->fromArray([
            [
                'Nguyễn Văn A',
                '0988111222',
                'a@example.com',
                'Nguyễn Thị B',
                '0988333444',
                'b@example.com',
                'Facebook Ads',
                '',
                '5000000',
                '',
                'new',
                now()->addDays(2)->format('Y-m-d'),
            ],
        ], null, 'A2');

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'mau-nhap-leads.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{imported:int, skipped:int, errors:array<int, string>}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['File không có dữ liệu.']];
        }

        $headerRow = array_shift($rows);
        $map = $this->mapHeaders($headerRow);

        if (! isset($map['name'])) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Thiếu cột bắt buộc: Họ tên *. Hãy dùng file mẫu.'],
            ];
        }

        $branches = Branch::where('is_active', true)->get();
        $salesUsers = User::whereIn('role', ['sales', 'admin', 'super_admin'])
            ->where('is_active', true)
            ->get();
        $defaultBranchId = CurrentBranch::id()
            ?? $branches->first()?->id;
        $forceSalesId = auth()->user()?->isSales() ? auth()->id() : null;
        $actorId = auth()->id();
        $assignmentNotifier = app(LeadAssignmentNotifier::class);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            if ($this->rowEmpty($row)) {
                $skipped++;
                continue;
            }

            $name = trim((string) ($row[$map['name']] ?? ''));
            $phone = isset($map['phone']) ? trim((string) ($row[$map['phone']] ?? '')) : '';

            if ($name === '') {
                $errors[] = "Dòng {$line}: thiếu họ tên.";
                $skipped++;
                continue;
            }

            $email = $this->nullableString($row, $map, 'email');
            if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Dòng {$line}: email không hợp lệ ({$email}).";
                $skipped++;
                continue;
            }

            $relatedEmail = $this->nullableString($row, $map, 'related_email');
            if ($relatedEmail && ! filter_var($relatedEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Dòng {$line}: email người liên quan không hợp lệ.";
                $skipped++;
                continue;
            }

            $branchId = $defaultBranchId;
            $branchName = $this->nullableString($row, $map, 'branch');
            if ($branchName) {
                $matched = $branches->first(fn (Branch $b) => mb_strtolower($b->name) === mb_strtolower($branchName));
                if (! $matched) {
                    $errors[] = "Dòng {$line}: không tìm thấy chi nhánh \"{$branchName}\".";
                    $skipped++;
                    continue;
                }
                $branchId = $matched->id;
            }

            if (! $branchId) {
                $errors[] = "Dòng {$line}: chưa chọn chi nhánh (điền cột Chi nhánh hoặc chọn chi nhánh trên header).";
                $skipped++;
                continue;
            }

            $salesId = $forceSalesId;
            if (! $forceSalesId) {
                $salesEmail = $this->nullableString($row, $map, 'sales');
                if ($salesEmail) {
                    $sales = $salesUsers->first(fn (User $u) => mb_strtolower($u->email) === mb_strtolower($salesEmail)
                        || mb_strtolower($u->name) === mb_strtolower($salesEmail));
                    if (! $sales) {
                        $errors[] = "Dòng {$line}: không tìm thấy Sales \"{$salesEmail}\".";
                        $skipped++;
                        continue;
                    }
                    $salesId = $sales->id;
                }
            }

            $status = $this->normalizeStatus($this->nullableString($row, $map, 'status') ?? 'new');
            if (! $status) {
                $errors[] = "Dòng {$line}: trạng thái không hợp lệ.";
                $skipped++;
                continue;
            }

            $revenueRaw = $this->nullableString($row, $map, 'expected_revenue');
            $expectedRevenue = 0;
            if ($revenueRaw !== null && $revenueRaw !== '') {
                $expectedRevenue = (float) preg_replace('/[^\d.]/', '', $revenueRaw);
            }

            $followUpAt = $this->parseDate($this->nullableString($row, $map, 'follow_up_at'));
            if (! $followUpAt && $salesId && $status === 'new') {
                $followUpAt = now()->addDays(2)->toDateString();
            }

            $lead = Lead::create([
                'name' => $name,
                'phone' => $phone !== '' ? $phone : null,
                'email' => $email,
                'related_name' => $this->nullableString($row, $map, 'related_name'),
                'related_phone' => $this->nullableString($row, $map, 'related_phone'),
                'related_email' => $relatedEmail,
                'source' => $this->nullableString($row, $map, 'source') ?: 'Khác',
                'branch_id' => $branchId,
                'expected_revenue' => $expectedRevenue,
                'assigned_sales_id' => $salesId,
                'status' => $status,
                'follow_up_at' => $followUpAt,
            ]);
            $assignmentNotifier->notifyIfAssigned($lead, $salesId, $actorId);
            $imported++;
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int>
     */
    protected function mapHeaders(array $headerRow): array
    {
        $aliases = [
            'name' => ['họ tên *', 'ho ten *', 'họ tên', 'ho ten', 'name', 'ten'],
            'phone' => ['sđt *', 'sdt *', 'sđt', 'sdt', 'phone', 'so dien thoai', 'số điện thoại', 'số điện thoại *'],
            'email' => ['email'],
            'related_name' => ['người thân - họ tên', 'nguoi than - ho ten', 'người liên quan - họ tên', 'nguoi lien quan - ho ten', 'related_name', 'phụ huynh', 'ten phu huynh'],
            'related_phone' => ['người thân - sđt', 'nguoi than - sdt', 'người liên quan - sđt', 'nguoi lien quan - sdt', 'related_phone', 'sdt phu huynh'],
            'related_email' => ['người thân - email', 'nguoi than - email', 'người liên quan - email', 'nguoi lien quan - email', 'related_email', 'email phu huynh'],
            'source' => ['nguồn', 'nguon', 'source'],
            'branch' => ['chi nhánh', 'chi nhanh', 'branch'],
            'expected_revenue' => ['doanh thu dự kiến', 'doanh thu du kien', 'expected_revenue', 'doanh thu'],
            'sales' => ['sales (email)', 'sales', 'sales email', 'email sales'],
            'status' => ['trạng thái', 'trang thai', 'status'],
            'follow_up_at' => ['hạn xử lý (yyyy-mm-dd)', 'hạn xử lý', 'han xu ly', 'follow_up_at', 'deadline', 'due date'],
        ];

        $map = [];
        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized === '') {
                continue;
            }
            foreach ($aliases as $field => $keys) {
                if (in_array($normalized, $keys, true) && ! isset($map[$field])) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    protected function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return $value;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     */
    protected function nullableString(array $row, array $map, string $field): ?string
    {
        if (! isset($map[$field])) {
            return null;
        }
        $value = trim((string) ($row[$map[$field]] ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    protected function rowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalizeStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return 'new';
        }

        $key = mb_strtolower(trim($status));
        $map = [
            'new' => 'new',
            'mới' => 'new',
            'moi' => 'new',
            'contacted' => 'contacted',
            'đã liên hệ' => 'contacted',
            'da lien he' => 'contacted',
            'interested' => 'interested',
            'quan tâm' => 'interested',
            'quan tam' => 'interested',
            'won' => 'won',
            'đã chốt' => 'won',
            'da chot' => 'won',
            'lost' => 'lost',
            'thất bại' => 'lost',
            'that bai' => 'lost',
        ];

        return $map[$key] ?? null;
    }

    protected function parseDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
