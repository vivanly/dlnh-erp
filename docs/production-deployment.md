# Phát hành DLNH ERP bằng Docker và Git

## Quyền chạy lệnh cơ sở dữ liệu

- `migrate:fresh` chỉ dành cho môi trường phát triển hoặc staging có dữ liệu bỏ được. Lệnh này xóa toàn bộ bảng; không chạy trên production, kể cả kèm `--seed`.
- Production chỉ chạy migration cộng dồn bằng `docker compose exec laravel.test php artisan migrate --force`.
- Người được phép phát hành là quản trị hệ thống được công ty chỉ định, có quyền vào máy chủ và cơ sở dữ liệu. Người này cần xác nhận đúng máy/môi trường, bản sao lưu gần nhất và kế hoạch khôi phục trước khi áp dụng migration.
- Seeder mặc định chỉ tạo danh mục phòng ban. Seeder không tạo tài khoản hoặc dữ liệu nhân sự.
- Tài khoản nhân viên được IT cấp qua phần Quản trị người dùng; mật khẩu ban đầu phải từ 12 ký tự, có chữ hoa, chữ thường, số và ký hiệu. Không lưu mật khẩu dùng chung trong Git, Docker image hay file seed.

## Quy trình phát hành

1. Tạo commit/tag Git đã được rà soát và chuyển đúng phiên bản đó lên máy chủ.
2. Xác nhận cấu hình `.env` là production, `APP_DEBUG=false`, thông tin DB đúng và có bản sao lưu đã kiểm tra khả năng khôi phục.
3. Nếu đợt phát hành có thay đổi database, đặt ứng dụng vào maintenance mode:

   ```powershell
   docker compose exec laravel.test php artisan down
   ```

4. Áp dụng migration, không xóa database:

   ```powershell
   docker compose exec laravel.test php artisan migrate --force
   ```

5. Làm mới cache cấu hình/route/view nếu cấu hình ứng dụng yêu cầu, rồi mở lại ứng dụng:

   ```powershell
   docker compose exec laravel.test php artisan optimize
   docker compose exec laravel.test php artisan up
   ```

6. Kiểm tra đăng nhập, quyền theo vai trò và các luồng chính; nếu có lỗi dữ liệu, dừng ghi nhận giao dịch và thực hiện kế hoạch khôi phục đã chuẩn bị.

Không chạy `migrate:fresh`, không chạy seeder tạo dữ liệu thử trên production, và không dùng `git reset --hard` trên máy chủ để thay thế quy trình phát hành.
