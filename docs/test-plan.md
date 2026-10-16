# Quy trình kiểm thử và nghiệm thu DLNH ERP

**Phiên bản:** 1.0  
**Mục đích:** kiểm tra chức năng nghiệp vụ, quyền truy cập và tính toàn vẹn dữ liệu trước khi phát hành cho người dùng.

## 1. Vai trò

| Vai trò | Trách nhiệm |
|---|---|
| Điều phối kiểm thử / QA tester | Chuẩn bị dữ liệu, chạy kịch bản, lưu bằng chứng, ghi lỗi, kiểm thử lại và tổng hợp kết quả. |
| Đại diện nghiệp vụ từng bộ phận | Xác nhận kết quả đúng quy trình thực tế và ký nghiệm thu luồng của bộ phận. |
| Lập trình viên | Phân tích lỗi, sửa lỗi, nêu bản sửa và hỗ trợ kiểm thử lại. |
| Quản trị hệ thống | Chuẩn bị staging, tài khoản, bản sao lưu và kế hoạch phát hành/khôi phục. |

Tester có thể là một người phụ trách nhiều luồng, nhưng mỗi luồng cần đại diện nghiệp vụ xác nhận.

## 2. Môi trường và nguyên tắc

- Chạy kiểm thử trên **staging** tách biệt production; không dùng dữ liệu thật nhạy cảm nếu chưa được phép.
- Ghi phiên bản ứng dụng, thời điểm, trình duyệt, tài khoản/vai trò và dữ liệu đầu vào cho mỗi lượt chạy.
- Dùng mã dữ liệu có tiền tố riêng, ví dụ `TST-2026-001`, để dễ tìm và dọn sau kiểm thử.
- Không sửa trực tiếp cơ sở dữ liệu để làm một kịch bản đạt; thao tác qua giao diện hoặc API được hỗ trợ.
- Với kiểm thử phá hủy dữ liệu, chỉ thực hiện trên staging sau khi đã chụp snapshot.
- Mỗi kết quả là **Đạt**, **Không đạt**, **Bị chặn**, hoặc **Chưa chạy**. Đạt cần bằng chứng (ảnh, mã đơn/lô, log hoặc kết quả truy vấn được phê duyệt).

## 3. Quy trình thực hiện

1. **Chuẩn bị:** triển khai bản cần nghiệm thu lên staging; xác nhận migrations, cấu hình mail/tệp, sao lưu và tài khoản theo vai trò.
2. **Kiểm tra khói:** đăng nhập, mở dashboard/menu, kiểm tra một trang chính mỗi nhóm; nếu lỗi cản trở diện rộng thì dừng và báo.
3. **Kiểm thử chức năng theo module:** chạy danh mục, mua hàng, kho/QC, bán hàng, kế hoạch/sản xuất, nhãn/giao hàng, truy xuất và quản trị.
4. **Kiểm thử luồng đầu cuối:** chạy các kịch bản trong mục 5 bằng dữ liệu liên kết xuyên suốt.
5. **Kiểm thử quyền và dữ liệu:** xác minh quyền cả ở menu lẫn URL/POST trực tiếp; xác minh tồn kho, số lượng, trạng thái và lịch sử sau mỗi thao tác.
6. **Ghi lỗi:** tạo ticket theo mẫu mục 7, gắn mức độ nghiêm trọng và bằng chứng; không gộp lỗi có nguyên nhân/kết quả khác nhau.
7. **Sửa và kiểm thử lại:** chạy lại bước lỗi trên cùng dữ liệu hoặc bộ dữ liệu mới, sau đó chạy hồi quy các luồng liên quan.
8. **Nghiệm thu:** QA tổng hợp kết quả; chủ quy trình xác nhận nghiệp vụ; quản trị xác nhận checklist phát hành. Chỉ phát hành khi đạt tiêu chí go-live.

## 4. Bộ kiểm thử tối thiểu

| ID | Khu vực | Kiểm tra tối thiểu |
|---|---|---|
| CAT | Danh mục | Tạo/sửa/tìm kiếm/import-export nếu dùng; mã trùng và dữ liệu sai bị chặn; danh mục ngừng dùng không xuất hiện khi lập giao dịch mới. |
| AUTH | Tài khoản & quyền | Đăng nhập sai/đúng, tài khoản bị khóa, đổi mật khẩu; vai trò được phép và bị cấm; thử mở URL và gửi POST trực tiếp khi không có quyền. |
| PO | Mua hàng | Lập PO từng loại hàng, gửi duyệt, duyệt/từ chối có lý do, sửa và gửi lại; không nhận vượt số lượng còn lại. |
| GRN | Nhận hàng & QC | Nhận nhiều đợt, nhập/trả; lô chờ QC không vào tồn khả dụng; QC đạt mới dùng được; QC không đạt không được xuất sản xuất. |
| INV | Tồn kho | Nhập/xuất/trả/cấp phát; tồn sau giao dịch đúng; chặn xuất vượt tồn; kiểm tra không ghi nhận trùng khi tải lại/gửi lại thao tác. |
| SO | Đơn bán | Lập đơn nhiều dòng, duyệt/từ chối, kiểm kho, lập kế hoạch; kiểm tra số lượng, khách hàng và trạng thái qua từng bước. |
| BOM | BOM/MRP | Tạo và duyệt BOM, đơn vị/quy đổi, tính nhu cầu; BOM nháp/chưa duyệt không được dùng theo quy tắc ứng dụng. |
| PROD | Sản xuất | Kế hoạch tháng/đơn hàng, tạo lệnh, xuất nguyên liệu theo lô, ghi nhận sản lượng và vật tư trả; chặn vượt lượng cho phép. |
| FG | Thành phẩm & QC | Tạo lô đầu ra, ghi lượng dự kiến/thực tế, báo cáo QC, phê duyệt và nhập kho; số lượng còn chờ nhập phải đúng. |
| SHIP | Đóng gói & giao | In nhãn, phân bổ lô, xác nhận đóng gói/giao; không giao vượt lượng hoặc dùng lô không khả dụng; tồn giảm đúng một lần. |
| TRACE | Truy xuất | Tìm theo lô NCC/lô nội bộ/lô thành phẩm; lần được nguồn vào, lệnh, QC và đơn liên quan; mã QR mở đúng thông tin được phép. |
| DOC | Hồ sơ & tệp | Tải/xem/tải xuống COA, PKN, hồ sơ sản phẩm; người không có quyền không đọc được tệp riêng tư; loại tệp/kích thước sai bị từ chối. |
| UI | Giao diện & xuất dữ liệu | Tìm kiếm, lọc, phân trang, locale, múi giờ, in/xuất Excel/PDF trên các trang nghiệp vụ chính. |
| OPS | Vận hành | Migrations trên bản sao staging, log lỗi, queue nếu sử dụng, lịch sao lưu và phục hồi thử từ bản sao. |

## 5. Kịch bản đầu cuối

### E2E-01: Nguyên liệu từ mua đến sản xuất

**Tiền điều kiện:** có NCC, danh mục nguyên liệu, sản phẩm và BOM đã duyệt; tài khoản Kinh doanh, người duyệt, Kho, QA/QC, Kế hoạch và Sản xuất.

1. Kinh doanh tạo PO với số lượng kiểm thử, gửi duyệt.
2. Người duyệt duyệt PO; kiểm tra trạng thái và lịch sử người duyệt.
3. Kho xác nhận hàng đến và nhận một phần; QA nhập số lô/COA cần thiết.
4. Trước QC đạt, xác minh lượng đó không dùng được cho sản xuất.
5. QC duyệt đạt; xác minh tồn khả dụng tăng đúng lượng nhận đạt.
6. Tạo đơn/kế hoạch sản xuất, phát hành lệnh; Kho cấp nguyên liệu theo lô.
7. Ghi sản lượng, vật tư sử dụng và trả; đối chiếu sổ giao dịch với tồn cuối.

**Đạt khi:** số lượng và trạng thái nhất quán ở PO, phiếu nhận, lô, ledger và lệnh; không thể dùng lô chờ/không đạt hoặc cấp phát vượt tồn.

### E2E-02: Đơn bán đến giao hàng và truy xuất

**Tiền điều kiện:** có khách hàng, sản phẩm, tồn/lô thành phẩm đạt QC và tài khoản Kinh doanh, người duyệt, QA, Kho.

1. Tạo đơn bán có một hoặc nhiều dòng; gửi duyệt và duyệt đơn.
2. Kho kiểm tra khả năng đáp ứng; Kế hoạch chuyển phần thiếu sang sản xuất nếu quy trình yêu cầu.
3. QA phân bổ lô thành phẩm cho dòng đơn; xác minh không vượt lượng còn phân bổ.
4. Kho in nhãn, xác nhận đóng gói rồi xác nhận giao.
5. Mở lịch sử đơn và truy xuất bằng mã lô/QR; đối chiếu khách hàng, sản phẩm, số lượng và các lô nguồn.

**Đạt khi:** trạng thái đi đúng thứ tự; không đóng gói/giao vượt số lượng hoặc từ lô chưa đạt; tồn giảm đúng một lần; truy xuất khớp hồ sơ nguồn.

### E2E-03: Từ chối, sửa lại và gửi duyệt lại

1. Tạo PO hoặc đơn bán hợp lệ, gửi duyệt.
2. Người duyệt từ chối với lý do; xác minh người lập thấy lý do và trạng thái.
3. Người lập sửa, gửi lại; người duyệt duyệt.

**Đạt khi:** lý do được lưu, bản sửa được giữ, lịch sử trạng thái đúng và không phát sinh nhận/xuất kho trước bước được phép.

### E2E-04: Kiểm soát truy cập và tệp riêng tư

1. Đăng nhập bằng vai trò không có quyền duyệt, quản trị hoặc xem hồ sơ QC riêng tư.
2. Thử thao tác qua menu, mở URL trực tiếp, gửi request POST và truy cập đường dẫn tệp.
3. Lặp lại với vai trò được phép.

**Đạt khi:** người không có quyền bị từ chối ở server và không đọc được dữ liệu/tệp; người được cấp quyền thao tác đúng chức năng.

## 6. Kiểm tra hồi quy sau sửa lỗi

- Sửa tính tồn/ledger: chạy lại PO nhận hàng, QC, trả NCC, cấp phát sản xuất, nhận thành phẩm và giao hàng; đối chiếu tồn trước/sau.
- Sửa quyền: chạy lại AUTH và E2E-04 với vai trò liên quan.
- Sửa đơn hàng/duyệt: chạy lại SO, SHIP và E2E-02/03.
- Sửa lô/QC/truy xuất: chạy GRN, FG, TRACE và kiểm tra quyền truy cập tệp.
- Sửa giao diện form: kiểm tra validate phía server, lỗi nhập liệu, lưu thành công, xem lại và các kích thước màn hình chính.

## 7. Mẫu ghi lỗi

```text
ID:
Tiêu đề:
Môi trường / phiên bản:
Vai trò và tài khoản kiểm thử (không ghi mật khẩu):
Kịch bản / dữ liệu liên quan:
Các bước tái hiện:
Kết quả mong đợi:
Kết quả thực tế:
Mức độ: Blocker / Critical / Major / Minor / Trivial
Tần suất: luôn luôn / thỉnh thoảng / một lần
Bằng chứng: ảnh, mã đơn/lô, thời gian, log đã che thông tin nhạy cảm
Trạng thái: Mới / Đang xử lý / Chờ kiểm thử lại / Đã xác nhận / Hoãn
Kết quả kiểm thử lại:
```

**Mức độ:** Blocker = không thể tiếp tục nghiệm thu hoặc nguy cơ dữ liệu/bảo mật nghiêm trọng; Critical = sai tồn, sai giao dịch hoặc lộ dữ liệu; Major = chức năng chính không dùng được nhưng có đường vòng; Minor = lỗi nhỏ có đường vòng; Trivial = trình bày/chính tả.

## 8. Tiêu chí phát hành (Go/No-Go)

Chỉ chuyển production khi tất cả điều kiện sau được xác nhận:

- Không còn lỗi Blocker/Critical chưa xử lý hoặc chưa có quyết định chấp nhận rủi ro bằng văn bản.
- Tất cả kịch bản đầu cuối bắt buộc đạt; không còn lỗi Major chặn quy trình cốt lõi.
- Đại diện Kinh doanh, Kho, QA/QC, Kế hoạch/Sản xuất và quản trị ký xác nhận phần mình phụ trách.
- Dữ liệu ban đầu đã nạp và đối chiếu; tổng tồn và mẫu giao dịch được chủ dữ liệu xác nhận.
- Đã thử sao lưu và khôi phục; biết ai trực hỗ trợ, cách báo lỗi và cách quay lại bản trước.
- Tài khoản production, phân quyền, HTTPS, cấu hình mail/tệp và log đã được rà soát.
- Có kế hoạch phát hành, thời gian theo dõi sau phát hành và người có quyền quyết định dừng/rollback.

## 9. Phiếu tổng kết nghiệm thu

```text
Bản phát hành:
Môi trường:
Thời gian chạy:
Tổng kịch bản: __  Đạt: __  Không đạt: __  Bị chặn: __  Chưa chạy: __
Lỗi còn mở theo mức độ: Blocker __ / Critical __ / Major __ / Minor __ / Trivial __
Sai lệch dữ liệu/tồn cần theo dõi:
Quyết định: GO / NO-GO
Điều kiện hoặc rủi ro được chấp nhận:
QA tester / ngày:
Đại diện nghiệp vụ / bộ phận / ngày:
Quản trị hệ thống / ngày:
```
