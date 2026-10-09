# Bản đồ bảng dữ liệu và trang quản lý

Tài liệu định hướng khi chỉnh sửa hệ thống DLNH ERP. Được tổng hợp từ `routes/web.php`, controllers, models, views và migrations hiện có; không phải kết quả introspection database đang chạy. Cấu trúc thực tế có thể khác nếu môi trường chưa chạy đủ migration.

## Tìm nhanh theo trang

| Khu vực/trang | Route/controller/view | Bảng chính và liên quan |
|---|---|---|
| Phòng ban | `departments.*` / `DepartmentController` / `resources/views/departments` | `departments`; `users.department_id` |
| Nhân viên | `users.*` / `UserController` / `users` | `users`, `departments`; `users` còn giữ chức vụ, địa chỉ, trạng thái, locale, avatar |
| PPCB | `ppcb.*` / `PpcbController` / `ppcb` | `ppcb`; được tham chiếu bởi `products`, `order_items`, lô thành phẩm và kế hoạch sản xuất |
| Sản phẩm | `products.*` / `ProductController` / `products` | `products`, `ppcb`; hồ sơ liên quan ở `product_regulatory_documents`, `product_quality_standards`, `product_storage_methods`, `product_unit_conversions` |
| Nguyên liệu/phụ liệu | `raw-materials.*`, `accessories.*` / `RawMaterialController`, `AccessoryController` | `raw_materials`, `accessories`; mua hàng dùng `purchase_order_items`, tồn kho dùng `material_stock_movements`, kiểm soát lô dùng `material_lots` |
| BOM/định mức | `boms.*` / `BomController` / `boms` | `product_boms` → `product_bom_items`; quy đổi ở `product_unit_conversions`; workflow duyệt lưu ngay trên `product_boms` |
| Nhà cung cấp | `suppliers.*` / `suppliercontroller` / `suppliers` | `suppliers`; được PO tham chiếu |
| Đơn mua | `purchase-orders.*` / `PurchaseOrderController` / `purchase-orders` | `purchase_orders` → `purchase_order_items`; liên quan `suppliers`, `users`, `goods_receipts`, `supplier_return_orders`, `material_lots` |
| Nhận hàng | `goods-receipts.*` / `GoodsReceiptController` / `goods-receipts` | `goods_receipts` → `goods_receipt_items` → `purchase_order_items`; tồn dược liệu còn ghi `inventory_movements` |
| Lô nhà cung cấp / QA | `qa.batches.*`, `supplier-batches.*` / `SupplierBatchController` / `qa`, `suppliers` | `supplier_batches`; gắn `products`, có thể liên kết dữ liệu truy xuất `traceability_lot_codes`; COA lưu đường dẫn file trên lô |
| Lô nguyên liệu / QA | `material-lots.*` / `MaterialLotController` / `material-lots` | `material_lots` → `purchase_order_items`; trạng thái nhận/QC và người thao tác trên lô |
| Trả nhà cung cấp | `supplier-returns.*` / `SupplierReturnController` | `supplier_return_orders`; liên kết item đơn mua hoặc catalog nguyên liệu (xem migration mở rộng) |
| Khách hàng | `customers.*` / `CustomerController` / `customers` | `customers` |
| Đơn bán | `orders.*`, approval queues / `OrderController`, `OrderItemController` / `orders` | `orders` → `order_items`; liên quan `customers`, `products`, `ppcb`, phân bổ lô `sales_order_lot_allocations`, in nhãn `sales_order_label_prints`, xuất vật tư `material_stock_movements` |
| Kế hoạch tháng | `production-monthly-plans.*` / `ProductionMonthlyPlanController` | `production_monthly_plans` → `production_monthly_plan_lines`; từ line có thể phát sinh `production_orders` |
| Lệnh sản xuất | `production-orders.*` / `ProductionController` / `production-orders`, `production` | `production_orders`; vật tư cần/xuất ở `production_order_materials`; lô vật tư ở `production_material_lots`; đầu ra ở `production_finished_batches`; đầu vào lô ở `production_batch_inputs`; allocations ở `production_order_batch_allocations` |
| Lô nội bộ / QA thành phẩm | `qa.internal-lots.*`, `qa.production-batches.*` / `ProductionController` | `production_finished_batches`; có thể thuộc lệnh hoặc là lô nội bộ độc lập; dữ liệu PPCB/QC/file báo cáo và trạng thái kho nằm trên lô |
| Kho | `warehouse.*` / `WarehouseController` | Tùy màn hình: `inventory_movements` cho tồn/lô dược liệu; `material_stock_movements` cho vật tư catalog; `orders`/`order_items` cho xuất bán; `production_finished_batches` cho nhận thành phẩm |
| Truy xuất | `traceability.*` / `TraceabilityController`, `TraceabilityManagementController` | `traceability_settings`, `traceability_lot_codes`; dữ liệu lô gốc vẫn ở `supplier_batches` và `production_finished_batches` |
| Nhãn | `labels.*` / `LabelController` | `sales_order_label_prints`, `orders`, `order_items`, phân bổ lô |
| Hồ sơ sản phẩm | `product-regulatory-documents.*`, `product-quality-standards.*`, `product-storage-methods.*` | Mỗi bảng có `product_id`, gắn vào `products` |
| Hồ sơ cá nhân | `profile.*` / `ProfileController` | `users` |

## Các luồng dữ liệu chính

### Mua hàng và nhập kho

`suppliers` → `purchase_orders` → `purchase_order_items` → `goods_receipts`/`goods_receipt_items` (dược liệu), hoặc `material_lots` và `material_stock_movements` (nguyên liệu/phụ liệu). Trả hàng xuất phát từ item PO và ghi vào `supplier_return_orders`. Khi sửa cách tính tồn, lần lượt tìm `GoodsReceiptController`, `MaterialReceiptController`, `SupplierReturnController`, `WarehouseController` và các service ledger/availability liên quan.

### Bán hàng và giao hàng

`customers` → `orders` → `order_items`; đơn hàng đi qua duyệt bán, kiểm tra kho, lập kế hoạch/sản xuất, đóng gói, giao hàng và phân bổ lô. Trạng thái approval/warehouse/packaging được thêm dần vào `orders` và `order_items`, không tách thành bảng workflow riêng. Bắt đầu ở `OrderController` và `WarehouseController`.

### Sản xuất và truy xuất

`production_monthly_plans` → `production_monthly_plan_lines` → `production_orders` → `production_order_materials` → `production_material_lots`; kết quả thành `production_finished_batches`, nối đầu vào bằng `production_batch_inputs`. Lô đầu ra được nhận/xuất theo `inventory_movements`, nối với đơn bán qua `sales_order_lot_allocations`. Truy xuất mã QR bổ sung `traceability_settings` và `traceability_lot_codes` nhưng thông tin chi tiết lô vẫn nằm ở bảng lô gốc.

## Quy tắc lần theo khi sửa

1. URL/route name trong `routes/web.php` cho biết controller action.
2. Controller thường chỉ ra view trả về (`view('...')`); view nằm dưới `resources/views`.
3. Model cho biết quan hệ Eloquent và `$table`/casts; migration mới nhất cho biết schema tích lũy.
4. Với form, kiểm tra cả validation, dữ liệu gửi lên từ Blade/JS, lưu trong controller/service, và trang index/show hiển thị lại.
5. Với số tồn/trạng thái workflow, tìm mọi nơi cập nhật cùng bảng. Đừng chỉ đổi giao diện: ledger hoặc transition có thể được cập nhật ở controller/service khác.

## Các điểm cần chú ý trong schema hiện tại

- Migrations là lịch sử thay đổi, không phải một schema gọn duy nhất. Đọc migrations theo thứ tự thời gian, đặc biệt trước khi đổi/drop cột.
- `internal_batches` có migration tạo, sau đó bị drop/recreate ở `2026_09_30_110000_drop_legacy_internal_batches.php`; cấu trúc cũ không còn là nguồn chuẩn.
- Tồn kho hiện tách hai họ: `inventory_movements` cho luồng dược liệu/lô thành phẩm và `material_stock_movements` cho raw materials/accessories. Kiểm tra đúng nhóm mặt hàng trước khi sửa số tồn.
- Catalog mới `raw_materials`/`accessories` mở rộng `purchase_order_items` và các luồng trả hàng, xuất sản xuất. Một số khóa `product_id` được nullable hoặc có tham chiếu kiểu catalog; xem các migration `2026_10_08_*` cùng controller trước khi thay đổi.
- `production_monthly_plan_lines` từng gắn BOM trực tiếp, rồi migration `2026_10_07_090000_decouple_monthly_plans_from_bom.php` đưa thêm PPCB và điều chỉnh quan hệ. Kiểm tra model/controller hiện hành thay vì suy luận từ migration tạo bảng ban đầu.
- Nhiều trường approval và người thao tác là cột bổ sung trực tiếp trên `orders`, `product_boms`, `production_orders`, `production_finished_batches`; thay đổi workflow thường đụng cả route, policy/kiểm tra phòng ban, controller và Blade.
- Routes trong `web.php` được đặt trong middleware `auth`; việc đăng nhập không đồng nghĩa mọi vai trò được phép thao tác. Kiểm tra điều kiện quyền trong controller/model và menu Blade.
- Các bảng Laravel nền tảng gồm `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`; bảng `notifications` là thông báo ứng dụng.

## Điểm vào trong code

- Routes: `routes/web.php`, `routes/auth.php`
- Schema lịch sử: `database/migrations/`
- Models: `app/Models/`
- Controllers: `app/Http/Controllers/`
- Giao diện: `resources/views/`
- Quyền theo phòng ban: `app/Models/User.php` và các kiểm tra trong controllers/views
- Dịch vụ nghiệp vụ: `app/Services/` (nếu có; tìm tên ledger, availability, allocation khi thay đổi tồn/lô)

