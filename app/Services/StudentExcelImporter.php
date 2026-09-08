<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CourseClass;
use App\Models\Student;
use App\Support\CurrentBranch;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentExcelImporter
{
    public const HEADERS = [
        'Họ tên *',
        'Chi nhánh',
        'Ngày sinh',
        'Giới tính',
        'SĐT học viên',
        'Email học viên',
        'SĐT người thân',
        'Tên người thân',
        'Email người thân',
        'Trạng thái',
        'Lớp học',
        'Địa chỉ',
        'Ghi chú',
    ];

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('HocVien');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->fromArray([
            [
                'Nguyễn Văn An',
                '',
                '15/03/2015',
                'Nam',
                '0911222333',
                'an@example.com',
                '0988111222',
                'Nguyễn Thị Bình',
                'binh@example.com',
                'studying',
                '',
                'Quận 1, TP.HCM',
                '',
            ],
        ], null, 'A2');

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'mau-nhap-hoc-vien.xlsx', [
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
        $classes = CourseClass::query()->orderBy('name')->get();
        $defaultBranchId = CurrentBranch::id() ?? $branches->first()?->id;

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
            if ($name === '') {
                $errors[] = "Dòng {$line}: thiếu họ tên.";
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

            $parentEmail = $this->nullableString($row, $map, 'parent_email');
            if ($parentEmail && ! filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Dòng {$line}: email người thân không hợp lệ ({$parentEmail}).";
                $skipped++;
                continue;
            }

            $email = $this->nullableString($row, $map, 'email');
            if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Dòng {$line}: email học viên không hợp lệ ({$email}).";
                $skipped++;
                continue;
            }

            $dobRaw = $row[$map['dob']] ?? null;
            $dob = null;
            if (isset($map['dob']) && $dobRaw !== null && trim((string) $dobRaw) !== '') {
                $dob = $this->parseDate($dobRaw);
                if (! $dob) {
                    $errors[] = "Dòng {$line}: ngày sinh không hợp lệ.";
                    $skipped++;
                    continue;
                }
            }

            $gender = $this->normalizeGender($this->nullableString($row, $map, 'gender'));
            $status = $this->normalizeStatus($this->nullableString($row, $map, 'status') ?? 'studying');
            if (! $status) {
                $errors[] = "Dòng {$line}: trạng thái không hợp lệ (studying/paused/graduated/dropped).";
                $skipped++;
                continue;
            }

            $classIds = [];
            $classNamesRaw = $this->nullableString($row, $map, 'classes');
            if ($classNamesRaw) {
                $parts = preg_split('/[;|]+/', $classNamesRaw) ?: [];
                foreach ($parts as $part) {
                    $className = trim($part);
                    if ($className === '') {
                        continue;
                    }
                    $matchedClass = $classes->first(fn (CourseClass $c) => mb_strtolower($c->name) === mb_strtolower($className));
                    if (! $matchedClass) {
                        $errors[] = "Dòng {$line}: không tìm thấy lớp \"{$className}\".";
                        $classIds = null;
                        break;
                    }
                    $classIds[] = $matchedClass->id;
                }
                if ($classIds === null) {
                    $skipped++;
                    continue;
                }
                $classIds = array_values(array_unique($classIds));
            }

            $student = Student::create([
                'branch_id' => $branchId,
                'name' => $name,
                'dob' => $dob,
                'gender' => $gender,
                'phone' => $this->nullableString($row, $map, 'phone'),
                'email' => $email,
                'parent_phone' => $this->nullableString($row, $map, 'parent_phone'),
                'parent_name' => $this->nullableString($row, $map, 'parent_name'),
                'parent_email' => $parentEmail,
                'status' => $status,
                'address' => $this->nullableString($row, $map, 'address'),
                'notes' => $this->nullableString($row, $map, 'notes'),
            ]);

            if ($classIds !== []) {
                $student->classes()->syncWithoutDetaching($classIds);
            }

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
            'name' => ['họ tên *', 'ho ten *', 'họ tên', 'ho ten', 'name', 'ten', 'tên học viên', 'ten hoc vien'],
            'branch' => ['chi nhánh', 'chi nhanh', 'branch'],
            'dob' => ['ngày sinh', 'ngay sinh', 'dob', 'birthday', 'date of birth'],
            'gender' => ['giới tính', 'gioi tinh', 'gender', 'sex'],
            'phone' => ['sđt học viên', 'sdt hoc vien', 'phone', 'sđt hv', 'sdt hv', 'điện thoại học viên', 'dien thoai hoc vien'],
            'email' => ['email học viên', 'email hoc vien', 'email hv', 'email'],
            'parent_phone' => ['sđt người thân', 'sdt nguoi than', 'parent_phone', 'sđt phụ huynh', 'sdt phu huynh'],
            'parent_name' => ['tên người thân', 'ten nguoi than', 'parent_name', 'phụ huynh', 'phu huynh'],
            'parent_email' => ['email người thân', 'email nguoi than', 'parent_email', 'email phụ huynh', 'email phu huynh'],
            'status' => ['trạng thái', 'trang thai', 'status'],
            'classes' => ['lớp học', 'lop hoc', 'classes', 'class', 'lớp', 'lop'],
            'address' => ['địa chỉ', 'dia chi', 'address'],
            'notes' => ['ghi chú', 'ghi chu', 'notes', 'note'],
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

    protected function parseDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                // fall through
            }
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $fmt) {
            $dt = \DateTime::createFromFormat('!'.$fmt, $str);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    protected function normalizeGender(?string $gender): ?string
    {
        if ($gender === null || $gender === '') {
            return null;
        }

        $key = mb_strtolower(trim($gender));
        $map = [
            'nam' => 'Nam',
            'male' => 'Nam',
            'm' => 'Nam',
            'nữ' => 'Nữ',
            'nu' => 'Nữ',
            'female' => 'Nữ',
            'f' => 'Nữ',
            'khác' => 'Khác',
            'khac' => 'Khác',
            'other' => 'Khác',
        ];

        return $map[$key] ?? (in_array($gender, ['Nam', 'Nữ', 'Khác'], true) ? $gender : null);
    }

    protected function normalizeStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return 'studying';
        }

        $key = mb_strtolower(trim($status));
        $map = [
            'studying' => 'studying',
            'đang học' => 'studying',
            'dang hoc' => 'studying',
            'paused' => 'paused',
            'bảo lưu' => 'paused',
            'bao luu' => 'paused',
            'graduated' => 'graduated',
            'hoàn thành' => 'graduated',
            'hoan thanh' => 'graduated',
            'dropped' => 'dropped',
            'nghỉ' => 'dropped',
            'nghi' => 'dropped',
        ];

        return $map[$key] ?? null;
    }
}
