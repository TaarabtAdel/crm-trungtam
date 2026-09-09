# CRM Trung Tâm

Hệ thống CRM / quản lý trung tâm đào tạo (Laravel 11): tuyển sinh, lớp học, học viên, điểm danh, tài chính và thông báo nội bộ.

## Tính năng chính

- **Tuyển sinh (CRM):** leads, phân Sales, lịch tương tác, dashboard pipeline, nhập Excel
- **Đào tạo:** môn học, giáo viên, lớp, thời khóa biểu, điểm danh
- **Học viên:** hồ sơ, gắn lớp, import Excel
- **Tài chính:** hóa đơn, công nợ, chi phí / duyệt chi, lương GV, hoa hồng, hoàn phí, báo cáo PDF
- **Đa chi nhánh** + phân quyền theo role
- **Thông báo in-app** (chuông): phân lead, nhắc hạn lead, buổi học chưa cập nhật trạng thái, chi phí, nợ học phí
- **Backup SQL** theo tenant vào `public/storage/{subdomain}/backups/` (không cần `storage:link`)

## Stack

| Thành phần | Phiên bản / ghi chú |
|------------|---------------------|
| PHP | 8.2+ (Docker image: **8.3**) |
| Laravel | 11 |
| MySQL | 8 |
| Frontend admin | Blade + Bootstrap 4 |
| Excel | PhpSpreadsheet |
| PDF | DomPDF |

---

## Yêu cầu

**Khuyến nghị dùng Docker** (đã có sẵn trong repo):

- [Docker](https://docs.docker.com/get-docker/) + Docker Compose v2
- Cổng trống: **8082** (app), **8083** (phpMyAdmin), **3307** (MySQL host)

Hoặc chạy local: PHP 8.2+, Composer, Node 20+, MySQL 8.

---

## Cài đặt trên cPanel (không SSH / không migrate)

1. Upload code, trỏ document root vào `public/`.
2. Tạo MySQL database trống + user (grant đầy đủ trên DB đó).
3. Chmod ghi được: `storage/`, `bootstrap/cache/`, file `.env` (copy từ `.env.example` nếu chưa có).
4. Mở trình duyệt: `https://your-domain.com/install`
5. Làm wizard:
   - Kiểm tra môi trường
   - Nhập Host / Port / Database / User / Pass → hệ thống ghi `.env` và **tạo bảng bằng code** (`App\Models\Versions\Ver1`, không cần `php artisan migrate`)
   - Tạo tài khoản Super Admin
6. Đăng nhập → dùng **Cài đặt nhanh** / Hướng dẫn trong app.

File khóa không dùng (nhiều subdomain chung 1 code). Hệ thống coi **đã cài** khi DB hiện tại đã có bảng `users`/`settings` và có ít nhất 1 user admin.

Nâng cấp schema sau này (khi có `Ver2`, `Ver3`…): chạy qua `SchemaUpdateService` (admin update — sẽ bổ sung UI nếu cần) hoặc gọi service sau deploy.

---

## Cài đặt (Docker)

```bash
git clone <url-repo> crm-trungtam
cd crm-trungtam

cp .env.example .env
# Tuỳ chỉnh nếu cần. Với Docker giữ DB_HOST=mysql
# Nên đặt: APP_TIMEZONE=Asia/Ho_Chi_Minh

docker compose up --build -d
```

Lần đầu, `entrypoint` sẽ:

- chờ MySQL sẵn sàng
- `composer install`
- tạo `APP_KEY` nếu chưa có
- `storage:link`
- build frontend nếu chưa có `public/build`

Sau đó migrate + seed:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force

# (Tuỳ chọn) dữ liệu demo đầy đủ hơn
docker compose exec app php artisan db:seed --class=DemoDataSeeder --force
```

### Truy cập

| Dịch vụ | URL / cổng |
|---------|------------|
| Ứng dụng | http://localhost:8082 |
| phpMyAdmin | http://localhost:8083 (user `root` / `secret`) |
| MySQL (từ máy host) | `127.0.0.1:3307` |

### Tài khoản mặc định (sau seed)

| Email | Mật khẩu | Role |
|-------|----------|------|
| `admin@crm.local` | `password` | Super Admin |
| `sales@crm.local` | `password` | Sales |
| `daotao@crm.local` | `password` | Đào Tạo |
| `teacher@crm.local` | `password` | Giáo viên (ghi nhật ký + xem lương GV) |

Mỗi user có mục **Bảng lương của tôi** (sidebar / avatar): chỉ Giáo viên → lương GV; role khác → lương NV; vừa GV vừa role khác → tab chọn cả hai.

> Đổi mật khẩu ngay trên môi trường thật.

---

## Multi-tenant (subdomain → database)

Giống mô hình `quanlythietbitruonghoc`: mỗi trung tâm một subdomain và một MySQL database riêng, cùng một bộ source code.

| Subdomain | Database (ví dụ) |
|-----------|------------------|
| `tpt-academy.quanlytrungtam.com` | `crmtt_tpt-academy` |
| `tht-global.quanlytrungtam.com` | `crmtt_tht-global` |

### `.env` production

```env
TENANT_RESOLVE=true
TENANT_BASE_DOMAIN=quanlytrungtam.com
TENANT_DATABASE_PREFIX=crmtt_
APP_URL=https://quanlytrungtam.com
# Cookie session theo từng host (để trống / null) — mỗi trung tâm login riêng
SESSION_DOMAIN=null
```

### Local / Docker

```env
TENANT_RESOLVE=false
# Dùng DB_DATABASE như bình thường (crm_trungtam)
```

### Khi thêm trung tâm mới (live)

1. DNS: `*.quanlytrungtam.com` → server (wildcard).
2. Tạo MySQL database: `{TENANT_DATABASE_PREFIX}{subdomain}` (vd `crmtt_tpt-academy`) và grant user app.
3. Cài schema **không cần SSH migrate** — tạm thời trỏ `.env` `DB_DATABASE` sang DB mới rồi mở `/install` (hoặc gọi `Ver1::doUpdate()` + seed admin trên DB đó). Nếu có SSH:
   ```bash
   DB_DATABASE=crmtt_tpt-academy php artisan migrate --force
   DB_DATABASE=crmtt_tpt-academy php artisan db:seed --force
   ```
4. Truy cập `https://tpt-academy.quanlytrungtam.com`.

Middleware `ResolveTenantDatabase` (prepend web) đổi connection MySQL theo subdomain trước khi session/auth chạy.

### File upload / backup (shared hosting)

- **Không cần** `php artisan storage:link`.
- Disk `public` trỏ thẳng `public/storage/{subdomain}/` (local Docker: `public/storage/local/`).
- Backup SQL: menu **Hệ thống → Backup dữ liệu** → lưu `public/storage/{subdomain}/backups/` (chặn truy cập HTTP trực tiếp; tải qua admin).

### Lệnh hữu ích

```bash
docker compose logs -f app
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose down          # dừng
docker compose down -v       # dừng + xoá volume MySQL
```

---

## Cài đặt (không Docker)

### Cách A — Wizard web (khuyến nghị shared hosting)

Copy `.env.example` → `.env` (có thể để DB tạm), `composer install`, `npm run build`, rồi mở `/install`.

### Cách B — Artisan (máy có SSH)

```bash
cp .env.example .env
# Sửa DB_HOST=127.0.0.1, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# APP_URL=http://localhost:8000
# APP_TIMEZONE=Asia/Ho_Chi_Minh

composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

npm install && npm run build
php artisan serve
```

---

## Cấu trúc thư mục (tóm tắt)

```
app/Http/Controllers/Admin/   # Controller admin
app/Models/                   # Eloquent
app/Services/                 # Nghiệp vụ (import Excel, tài chính, …)
app/Notifications/            # Thông báo database
app/Console/Commands/         # Lệnh nhắc định kỳ
resources/views/admin/        # Blade UI
routes/web.php                # Route admin
routes/console.php            # Lịch scheduler
config/permissions.php        # Role & quyền
database/migrations/
```

---

## Scheduler & nhắc tự động

Timezone theo `APP_TIMEZONE` trong `.env` (nên `Asia/Ho_Chi_Minh`).

| Lệnh | Tần suất (qua tick) | Mục đích |
|------|---------------------|----------|
| `finance:remind-debts` | Mỗi ngày từ **08:00** | Nhắc nợ học phí (in-app) |
| `crm:remind-lead-followups` | Mỗi ngày từ **08:15** | Lead sắp tới / quá hạn follow-up |
| `crm:remind-stale-sessions` | **Mỗi giờ** | Buổi đã kết thúc vẫn **Đã lên lịch** |
| `crm:remind-upcoming-sessions` | **Mỗi 15 phút** | Nhắc HV/PH lịch học (Email/Zalo) **trước buổi ~2 tiếng** |

Tick gọi `ReminderScheduler` — chỉ chạy lệnh **đến hạn** và **chưa chạy** trong khung (cache + lock).

### 1) Cron GET (khuyến nghị production — không cần đăng nhập)

1. Thêm token vào `.env`:

```env
SCHEDULER_TICK_TOKEN=chuỗi_bí_mật_dài
```

Sinh token: `openssl rand -hex 24`

2. Cài cron **mỗi phút** trên cPanel / crontab:

```cron
* * * * * curl -fsS "https://TENANT.quanlytrungtam.com/scheduler/tick?key=DIEN_TOKEN" >/dev/null 2>&1
```

Ví dụ local/Docker:

```bash
curl -fsS "http://localhost:8082/scheduler/tick?key=$(grep ^SCHEDULER_TICK_TOKEN= .env | cut -d= -f2)"
```

Multi-tenant: **mỗi subdomain một dòng cron** (middleware chọn DB theo host).

Endpoint:

```http
GET /scheduler/tick?key=SCHEDULER_TICK_TOKEN
```

Tuỳ chọn: `&force=1` ép chạy mọi job (chỉ khi test).

### 2) AJAX khi đăng nhập admin (dự phòng)

Khi staff mở `/admin`, layout gọi `POST /admin/scheduler/tick` (CSRF + session), tối đa mỗi **60 giây** / trình duyệt.

> Nếu cả ngày không ai login và **không** có cron GET → nhắc (gồm lịch học) sẽ không chạy.

### 3) Laravel `schedule:run` (tuỳ chọn)

Nếu host hỗ trợ artisan scheduler:

```cron
* * * * * cd /home/USER/path/to/crm-trungtam && php artisan schedule:run >> /dev/null 2>&1
```

Lịch khai báo trong `routes/console.php` (gồm `crm:remind-upcoming-sessions` mỗi 15 phút). Có thể dùng song song với GET tick (đã chống trùng).

### Chạy tay (Docker / SSH)

```bash
docker compose exec app php artisan finance:remind-debts
docker compose exec app php artisan crm:remind-lead-followups
docker compose exec app php artisan crm:remind-stale-sessions
docker compose exec app php artisan crm:remind-upcoming-sessions --minutes=120 --window=12

# Ép chạy toàn bộ job trong ReminderScheduler
docker compose exec app php artisan tinker --execute="print_r(app(App\Services\ReminderScheduler::class)->tick(true));"
```

### Nhắc lịch học (Zalo / Email)

1. Cài đặt → bật **Kênh Zalo** + **Nhắc lịch học (Zalo trước buổi 2 tiếng)**.
2. Mẫu thông báo `session_reminder`: bật Zalo, điền **Zalo Template ID**, JSON biến dạng `{"customer_name":"{{recipient_name}}","date":"{{session_date}}",...}`.
3. Cron GET / tick chạy `crm:remind-upcoming-sessions` mỗi ~15 phút; gửi HV + PH có SĐT/Email; chống trùng qua `notification_logs`.

### Điều kiện nhận thông báo in-app

- User `is_active` và đúng quyền / gán lead:
  - Nợ: `DebtReminderService`
  - Lead: Sales được gán (`assigned_sales_id`)
  - Buổi stale: quyền `training.classes.manage`

### File liên quan

| File | Vai trò |
|------|---------|
| `GET /scheduler/tick` | Cron công khai (key) |
| `POST /admin/scheduler/tick` | AJAX admin |
| `app/Services/ReminderScheduler.php` | Quyết định lệnh đến hạn |
| `routes/console.php` | Lịch `schedule:run` |
| `app/Console/Commands/*` | Các lệnh nhắc |
| `app/Services/SessionReminderNotificationService.php` | Nhắc lịch → Email/Zalo |

---

## License

Nội bộ / theo thỏa thuận dự án.
