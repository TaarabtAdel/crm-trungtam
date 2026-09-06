<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Student;
use App\Models\Teacher;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Http\Request;

class DemoDataController extends Controller
{
    public function index()
    {
        $summary = [
            'teachers' => Teacher::count(),
            'students' => Student::count(),
            'classes' => CourseClass::count(),
            'leads' => Lead::count(),
            'invoices' => Invoice::count(),
        ];

        return view('admin.system.demo_data', compact('summary'));
    }

    public function run(Request $request)
    {
        (new DemoDataSeeder)->run();

        return redirect()
            ->route('admin.demo-data.index')
            ->with('success', 'Đã khởi tạo dữ liệu demo thành công. Bạn có thể duyệt các module để chạy thử.');
    }
}
