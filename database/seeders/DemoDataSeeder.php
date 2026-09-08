<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\Interaction;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->clearDemoBusinessData();

            $main = Branch::query()->firstOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => 'TPT2 - Cơ sở chính',
                    'address' => 'Cầu Giấy, Hà Nội',
                    'phone' => '0900000000',
                    'is_active' => true,
                ]
            );

            $branch2 = Branch::query()->firstOrCreate(
                ['code' => 'HN2'],
                [
                    'name' => 'TPT2 - Cơ sở 2',
                    'address' => 'Thanh Xuân, Hà Nội',
                    'phone' => '0911111111',
                    'is_active' => true,
                ]
            );

            $admin = User::query()->updateOrCreate(
                ['email' => 'admin@crm.local'],
                [
                    'name' => 'admin',
                    'password' => Hash::make('password'),
                    'role' => 'super_admin',
                    'branch_id' => $main->id,
                    'phone' => '0901000001',
                    'is_active' => true,
                ]
            );

            $sales1 = User::query()->updateOrCreate(
                ['email' => 'sales@crm.local'],
                [
                    'name' => 'Nguyễn Thị Sales',
                    'password' => Hash::make('password'),
                    'role' => 'sales',
                    'branch_id' => $main->id,
                    'phone' => '0902000002',
                    'is_active' => true,
                ]
            );

            $sales2 = User::query()->updateOrCreate(
                ['email' => 'sales2@crm.local'],
                [
                    'name' => 'Trần Văn Tư Vấn',
                    'password' => Hash::make('password'),
                    'role' => 'sales',
                    'branch_id' => $branch2->id,
                    'phone' => '0903000003',
                    'is_active' => true,
                ]
            );

            User::query()->updateOrCreate(
                ['email' => 'daotao@crm.local'],
                [
                    'name' => 'Lê Thị Đào Tạo',
                    'password' => Hash::make('password'),
                    'role' => 'training',
                    'branch_id' => $main->id,
                    'phone' => '0904000004',
                    'is_active' => true,
                ]
            );

            $subjectNames = [
                [$main->id, 'Lập trình Python cơ bản', 'Khóa nhập môn Python cho học sinh cấp 2-3'],
                [$main->id, 'Scratch & Tư duy logic', 'Lập trình kéo thả cho học sinh tiểu học'],
                [$main->id, 'Web Frontend (HTML/CSS/JS)', 'Xây dựng website cơ bản'],
                [$branch2->id, 'Robotics Arduino', 'Lắp ráp và lập trình robot'],
                [$branch2->id, 'Tin học văn phòng', 'Word, Excel, PowerPoint'],
            ];

            $subjects = collect($subjectNames)->map(function ($row) {
                return Subject::query()->create([
                    'branch_id' => $row[0],
                    'name' => $row[1],
                    'description' => $row[2],
                    'status' => 'active',
                ]);
            });

            $teachersData = [
                [$main->id, 'Nguyễn Tài Tâm', 'tam.gv@crm.local', '0912345678', 'Python', 'Thạc sĩ CNTT', 250000],
                [$main->id, 'Lê Minh Châu', 'chau.gv@crm.local', '0912345679', 'Scratch', 'Cử nhân Sư phạm', 200000],
                [$branch2->id, 'Phạm Quốc Huy', 'huy.gv@crm.local', '0912345680', 'Robotics', 'Kỹ sư Điện tử', 280000],
            ];

            $teachers = collect($teachersData)->map(function ($row) {
                return Teacher::query()->create([
                    'branch_id' => $row[0],
                    'name' => $row[1],
                    'email' => $row[2],
                    'phone' => $row[3],
                    'specialty' => $row[4],
                    'qualification' => $row[5],
                    'hourly_rate' => $row[6],
                    'joined_at' => now()->subMonths(rand(3, 18))->toDateString(),
                    'notes' => 'Giáo viên demo',
                    'status' => 'active',
                ]);
            });

            $classes = collect([
                [
                    'branch_id' => $main->id,
                    'name' => 'Python K1 - Tối T3/T5',
                    'code' => 'PY-K1',
                    'subject_id' => $subjects[0]->id,
                    'teacher_id' => $teachers[0]->id,
                    'schedule_days' => ['T3', 'T5'],
                    'start_time' => '18:00',
                    'end_time' => '20:00',
                    'room' => 'P201',
                    'max_students' => 15,
                    'start_date' => now()->subMonths(1)->toDateString(),
                    'end_date' => now()->addMonths(2)->toDateString(),
                    'tuition_fee' => 2500000,
                    'tuition_type' => 'monthly',
                    'status' => 'active',
                ],
                [
                    'branch_id' => $main->id,
                    'name' => 'Scratch K2 - CN sáng',
                    'code' => 'SC-K2',
                    'subject_id' => $subjects[1]->id,
                    'teacher_id' => $teachers[1]->id,
                    'schedule_days' => ['CN'],
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'room' => 'P105',
                    'max_students' => 12,
                    'start_date' => now()->subWeeks(3)->toDateString(),
                    'end_date' => now()->addMonths(3)->toDateString(),
                    'tuition_fee' => 350000,
                    'tuition_type' => 'per_session',
                    'status' => 'active',
                ],
                [
                    'branch_id' => $branch2->id,
                    'name' => 'Arduino K1 - T7',
                    'code' => 'RO-K1',
                    'subject_id' => $subjects[3]->id,
                    'teacher_id' => $teachers[2]->id,
                    'schedule_days' => ['T7'],
                    'start_time' => '14:00',
                    'end_time' => '16:30',
                    'room' => 'Lab A',
                    'max_students' => 10,
                    'start_date' => now()->subMonths(2)->toDateString(),
                    'end_date' => now()->addMonth()->toDateString(),
                    'tuition_fee' => 3200000,
                    'tuition_type' => 'monthly',
                    'status' => 'active',
                ],
            ])->map(fn ($data) => CourseClass::query()->create($data));

            $studentsData = [
                [$main->id, 'Nguyễn Minh An', 'Nam', '0908111222', 'Nguyễn Văn Bình'],
                [$main->id, 'Trần Thu Hà', 'Nữ', '0908222333', 'Trần Thị Lan'],
                [$main->id, 'Lê Đức Huy', 'Nam', '0908333444', 'Lê Văn Khoa'],
                [$main->id, 'Phạm Ngọc Mai', 'Nữ', '0908444555', 'Phạm Thị Hoa'],
                [$branch2->id, 'Hoàng Gia Bảo', 'Nam', '0908555666', 'Hoàng Văn Nam'],
                [$branch2->id, 'Đỗ Khánh Linh', 'Nữ', '0908666777', 'Đỗ Thị Hằng'],
            ];

            $students = collect($studentsData)->map(function ($row, $i) {
                return Student::query()->create([
                    'branch_id' => $row[0],
                    'name' => $row[1],
                    'dob' => now()->subYears(rand(10, 15))->subDays(rand(1, 200))->toDateString(),
                    'gender' => $row[2],
                    'parent_phone' => $row[3],
                    'parent_name' => $row[4],
                    'parent_email' => 'phuhuynh'.($i + 1).'@example.com',
                    'status' => 'studying',
                    'address' => 'Hà Nội',
                    'notes' => null,
                ]);
            });

            $classes[0]->students()->sync([$students[0]->id, $students[1]->id, $students[2]->id]);
            $classes[1]->students()->sync([$students[1]->id, $students[3]->id]);
            $classes[2]->students()->sync([$students[4]->id, $students[5]->id]);

            foreach ([$classes[0], $classes[1], $classes[2]] as $class) {
                for ($i = 1; $i <= 4; $i++) {
                    $date = now()->subWeeks($i)->toDateString();
                    ClassSession::query()->create([
                        'class_id' => $class->id,
                        'teacher_id' => $class->teacher_id,
                        'session_date' => $date,
                        'start_time' => $class->start_time,
                        'end_time' => $class->end_time,
                        'status' => 'completed',
                        'notes' => 'Buổi học demo',
                    ]);

                    foreach ($class->students as $student) {
                        Attendance::query()->create([
                            'class_id' => $class->id,
                            'student_id' => $student->id,
                            'session_date' => $date,
                            'status' => collect(['present', 'present', 'present', 'late', 'absent'])->random(),
                        ]);
                    }
                }
            }

            $leadsData = [
                ['Nguyễn Văn A', '0988111001', 'Facebook Ads', 'new', $sales1->id, 5000000, $main->id],
                ['Trần Thị B', '0988111002', 'Zalo', 'contacted', $sales1->id, 3500000, $main->id],
                ['Lê Hoàng C', '0988111003', 'Giới thiệu', 'interested', $sales1->id, 4500000, $main->id],
                ['Phạm Thu D', '0988111004', 'Google', 'won', $sales2->id, 6000000, $branch2->id],
                ['Hoàng Minh E', '0988111005', 'Walk-in', 'lost', $sales2->id, 2000000, $branch2->id],
                ['Đặng Mỹ F', '0988111006', 'Facebook Ads', 'new', $sales2->id, 4800000, $main->id],
            ];

            $leads = collect($leadsData)->map(function ($row) {
                return Lead::query()->create([
                    'name' => $row[0],
                    'phone' => $row[1],
                    'email' => strtolower(str_replace(' ', '', $row[0])).'@gmail.com',
                    'source' => $row[2],
                    'status' => $row[3],
                    'assigned_sales_id' => $row[4],
                    'expected_revenue' => $row[5],
                    'branch_id' => $row[6],
                    'created_at' => now()->subDays(rand(0, 10)),
                    'updated_at' => now(),
                ]);
            });

            Interaction::query()->create([
                'lead_id' => $leads[0]->id,
                'sales_id' => $sales1->id,
                'branch_id' => $main->id,
                'type' => 'Cuộc gọi',
                'scheduled_at' => now()->setTime(10, 0),
                'status' => 'upcoming',
                'notes' => 'Gọi tư vấn khóa Python',
            ]);

            Interaction::query()->create([
                'lead_id' => $leads[2]->id,
                'sales_id' => $sales1->id,
                'branch_id' => $main->id,
                'type' => 'Lịch hẹn Test',
                'scheduled_at' => now()->addDay()->setTime(15, 30),
                'status' => 'upcoming',
                'notes' => 'Test năng lực đầu vào',
            ]);

            Interaction::query()->create([
                'lead_id' => $leads[3]->id,
                'sales_id' => $sales2->id,
                'branch_id' => $branch2->id,
                'type' => 'Gặp trực tiếp',
                'scheduled_at' => now()->subDays(2)->setTime(9, 0),
                'status' => 'done',
                'notes' => 'Đã chốt học Robotics',
            ]);

            Invoice::query()->create([
                'student_id' => $students[0]->id,
                'class_id' => $classes[0]->id,
                'amount' => 2500000,
                'billing_month' => now()->format('Y-m'),
                'fee_type' => 'monthly',
                'sessions_count' => null,
                'status' => 'paid',
                'due_date' => now()->subDays(5)->toDateString(),
                'paid_at' => now()->subDays(3),
                'note' => 'Học phí tháng hiện tại',
            ]);

            $scratchSessions = ClassSession::query()
                ->where('class_id', $classes[1]->id)
                ->where('status', 'completed')
                ->whereMonth('session_date', now()->month)
                ->whereYear('session_date', now()->year)
                ->count();

            Invoice::query()->create([
                'student_id' => $students[1]->id,
                'class_id' => $classes[1]->id,
                'amount' => 350000 * max($scratchSessions, 1),
                'billing_month' => now()->format('Y-m'),
                'fee_type' => 'per_session',
                'sessions_count' => max($scratchSessions, 1),
                'status' => 'unpaid',
                'due_date' => now()->addDays(7)->toDateString(),
                'note' => 'Học phí theo buổi Scratch',
            ]);

            Invoice::query()->create([
                'student_id' => $students[4]->id,
                'class_id' => $classes[2]->id,
                'amount' => 3200000,
                'billing_month' => now()->subMonth()->format('Y-m'),
                'fee_type' => 'monthly',
                'status' => 'paid',
                'due_date' => now()->subMonth()->endOfMonth()->toDateString(),
                'paid_at' => now()->subMonth()->addDays(10),
            ]);

            // keep unused vars referenced for clarity / static analysis
            unset($admin);
        });
    }

    protected function clearDemoBusinessData(): void
    {
        Attendance::query()->delete();
        ClassSession::query()->delete();
        Invoice::query()->delete();
        Interaction::query()->delete();
        Lead::query()->delete();
        DB::table('class_student')->delete();
        CourseClass::query()->delete();
        Student::query()->delete();
        Teacher::query()->delete();
        Subject::query()->delete();
    }
}
