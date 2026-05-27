# PHP Sandbox

Nền tảng học tập và kiểm tra PHP được xây dựng bằng Laravel, tập trung vào quản lý lớp học, bài tập, bài thi và môi trường sandbox để học viên thực thi code trực tiếp trên hệ thống.

## Tính năng chính

- Xác thực tài khoản, đăng ký và đăng nhập người dùng.
- Khu vực học viên: trang chủ, danh sách lớp học, bài tập, bài thi và trình soạn thảo sandbox.
- Khu vực quản trị: dashboard, quản lý người dùng, lớp học, bài tập, bài thi, bài nộp, thông báo, nhật ký và cài đặt hệ thống.
- API sandbox để chạy code, theo dõi trạng thái job, xem preview và gửi bài.
- Hỗ trợ hàng đợi xử lý nền, logging và cấu hình môi trường riêng cho sandbox.

## Công nghệ sử dụng

- Laravel 13
- PHP 8.3+
- Vite
- Tailwind CSS
- MySQL hoặc SQLite tùy môi trường triển khai

## Yêu cầu hệ thống

- PHP 8.3 trở lên
- Composer
- Node.js 18+ và npm
- CSDL MySQL hoặc SQLite

## Cài đặt

```bash
git clone <repository-url>
cd phpsanbox
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
```

Nếu bạn dùng SQLite, hãy tạo file cơ sở dữ liệu trước khi migrate và cập nhật biến môi trường tương ứng trong `.env`.

## Chạy dự án ở môi trường phát triển

```bash
php artisan serve
npm run dev
```

Nếu cần chạy đầy đủ như môi trường phát triển nội bộ của dự án, bạn có thể dùng script sẵn có:

```bash
composer run dev
```

Script này sẽ chạy đồng thời server Laravel, queue listener, log viewer và Vite.

## Build cho production

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Kiểm thử

```bash
composer test
```

## Cấu trúc dự án

- `app/Http/Controllers`: controller cho học viên và quản trị.
- `app/Services`: các lớp nghiệp vụ cho sandbox, database provisioning, submission và logging.
- `app/Jobs`: xử lý tác vụ nền.
- `routes/web.php`: định tuyến cho khu vực học viên, sandbox và admin.
- `sandbox-service/`: thành phần thực thi sandbox độc lập.

## Ghi chú triển khai

- Hệ thống có các route cho khu vực admin, sandbox API và khu vực học viên nên cần cấu hình xác thực, queue và biến môi trường đầy đủ trước khi chạy thực tế.
- Nếu bạn deploy bằng Docker, hãy kiểm tra các file `docker-compose*.yml` và `deploy.sh` đi kèm trong repo.

## Giấy phép

Dự án được phát hành dưới giấy phép MIT.
