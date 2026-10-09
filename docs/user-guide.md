# Hướng dẫn sử dụng DLNH ERP

**Tài liệu thao tác theo vai trò · Phiên bản 1.2 · Rà soát theo code ngày 09/10/2026**

Tài liệu này mô tả cách sử dụng các màn hình nghiệp vụ đang có trong ứng dụng: chuẩn bị danh mục, mua hàng, nhận hàng, kiểm soát chất lượng, bán hàng, lập kế hoạch, sản xuất, nhập kho, giao hàng và truy xuất. Tên nút và trường được ghi theo giao diện hiện tại của hệ thống.

> **Lưu ý về quyền:** Chỉ đăng nhập bằng tài khoản được cấp. Màn hình và nút thao tác phụ thuộc phòng ban/chức danh; máy chủ kiểm tra quyền khi lưu, duyệt hoặc xuất kho. Riêng danh sách nhân sự và quản lý phòng ban chỉ IT được vào. Hệ thống hiện không có luồng đăng ký công khai.

## Mục lục

1. Bắt đầu và đọc trạng thái
2. Vai trò và menu
3. Chuẩn bị danh mục
4. Mua hàng và nhận hàng
5. Kiểm tra chất lượng lô nhà cung cấp
6. Lập đơn bán và xử lý đơn hàng
7. BOM, MRP và duyệt kế hoạch
8. Lệnh sản xuất và nhập thành phẩm
9. Nhãn, đóng gói và giao hàng
10. Tồn kho, truy xuất và quản trị
11. Xử lý tình huống thường gặp
12. Danh sách kiểm tra bàn giao công việc

## 1. Bắt đầu và đọc trạng thái

1. Đăng nhập bằng tài khoản cá nhân được cấp. Tài khoản phải còn hiệu lực và được gán phòng ban/chức danh phù hợp.
2. Từ thanh điều hướng bên trái, mở nhóm công việc rồi chọn màn hình cần xử lý. Chọn **Tổng quan** để quay về dashboard.
3. Trên màn danh sách, dùng ô tìm kiếm và bộ lọc trạng thái để thu hẹp kết quả. Mở một mã đơn/mã lệnh để xem chi tiết trước khi thực hiện nghiệp vụ.
4. Sau khi lưu hoặc duyệt, đọc thông báo thành công/lỗi và kiểm tra lại trạng thái mới. Lỗi xác thực thường được hiển thị ngay trên màn hình; sửa trường được nêu rồi gửi lại.
5. Sử dụng các bộ lọc và phân trang để tìm hồ sơ cũ. Mã đơn, mã lô và mã lệnh là các thông tin tra cứu hữu ích nhất.

### Trạng thái cần phân biệt

- **Nháp / Draft:** hồ sơ chưa gửi duyệt; có thể còn chỉnh sửa.
- **Chờ duyệt / Pending:** đang nằm trong hàng đợi của người có quyền phê duyệt.
- **Bị từ chối / Rejected:** xem lý do, sửa hồ sơ nếu được phép rồi gửi lại.
- **Đã duyệt:** cho phép bước nghiệp vụ kế tiếp; không đồng nghĩa với đã nhập kho hoặc đã giao hàng.
- **Chờ QC:** lô chưa được phép sử dụng cho đến khi quy trình QC xác nhận đạt.
- **Đã đóng gói:** Kho ghi nhận đóng hàng; chưa phải đã gửi và chưa trừ tồn.
- **Hoàn thành:** bước cuối tương ứng đã được chốt. Kiểm tra hồ sơ và số lượng trước khi coi là hoàn tất.

## 2. Vai trò và menu

Menu có thể thay đổi theo quyền. Bảng dưới đây mô tả nơi thường bắt đầu công việc, không thay thế ma trận phân quyền do doanh nghiệp ban hành.

| Nhóm người dùng | Menu và công việc chính |
|---|---|
| Ban Giám Đốc / người duyệt | Duyệt đơn mua, duyệt đơn bán, duyệt kế hoạch sản xuất/tháng và BOM theo quyền tài khoản. Hàng đợi chỉ thao tác được khi controller xác nhận quyền; menu có thể không hiện đồng nhất với quyền được cấp. |
| Kinh doanh | Quản lý khách hàng, nhà cung cấp, đơn bán và đơn mua; theo dõi trạng thái và phản hồi từ bước duyệt. |
| QA | Cập nhật số lô NCC/COA cho nguyên liệu thô; tạo mã lô thành phẩm nội bộ và lượng dự kiến độc lập với sản lượng/lệnh; phân bổ lô cho đơn; theo dõi sản lượng hoàn thành riêng. |
| Kế hoạch | Lập kế hoạch theo đơn và kế hoạch sản xuất tháng; nhập sản lượng tháng thủ công; quản lý BOM. MRP là màn tính dựa trên BOM sản phẩm đã duyệt. |
| Sản xuất | Xem lệnh sản xuất, chốt sản lượng và nguyên liệu trả; xác nhận đóng gói; xem/in nhãn. |
| Kho | Nhận đơn mua, lập phiếu nhận; kiểm tra tồn đơn bán; xuất nguyên liệu sản xuất; nhập thành phẩm; đóng hàng và xác nhận gửi. |
| QC | Kết luận đạt/không đạt cho lô nguyên liệu thô, phụ liệu và cập nhật PKN cho lô thành phẩm theo quyền. |
| IT / Quản trị kỹ thuật | Tạo/cấp và quản lý tài khoản, là nhóm duy nhất được xem danh sách nhân sự, quản lý phòng ban; có quyền hỗ trợ một số màn hình theo controller. **IT không phải một bước phê duyệt nghiệp vụ.** |

> Đây là mô tả nhóm công việc, không phải bảng cấp quyền. Controller có thể cho phép Giám đốc/Kế hoạch/IT theo từng thao tác; menu sidebar hiện tại có một số mục (đặc biệt BOM) không hiển thị cho mọi tài khoản được controller cho phép. Nếu không thấy mục, xác nhận quyền và URL với quản trị hệ thống; không suy ra rằng chức năng đã bị vô hiệu.

## 3. Chuẩn bị danh mục

Chỉ người được giao quản lý dữ liệu chuẩn mới nên tạo hoặc sửa danh mục. Dữ liệu sai có thể ảnh hưởng đơn hàng, định mức, quy đổi và tồn kho.

### 3.1. Sản phẩm / dược liệu

1. Mở **Kinh doanh → Sản phẩm** (mục sản phẩm có thể nằm ngoài nhóm tùy bố trí menu).
2. Chọn **Thêm mới**.
3. Nhập tối thiểu **Mã hàng (SKU)** và **Tên hàng hóa**. Điền thêm **Phân loại**, **Đơn vị tính**, **Nguồn gốc**, **Bộ phận dùng**, **PPCB**, **Tên khoa học**, **Mã GTIN**, tài liệu tham khảo và ghi chú khi có dữ liệu được xác nhận.
4. Chọn **Lưu sản phẩm**. Nếu nhập sai hoặc trùng mã, đọc thông báo lỗi, sửa dữ liệu rồi lưu lại.
5. Khi cần nạp/cập nhật danh mục hàng loạt, dùng chức năng **Import/Export** nếu tài khoản được cấp quyền. Kiểm tra cấu trúc mẫu và dữ liệu trước khi nhập.

### 3.2. Khách hàng

1. Mở **Kinh doanh → Khách hàng → Thêm mới**.
2. Nhập **Mã khách hàng**, **Tên khách hàng/đơn vị**, **Phân loại**; có thể bổ sung giấy phép kinh doanh, điện thoại, email, địa chỉ và ghi chú.
3. Chọn **Lưu khách hàng**. Mã và tên là trường bắt buộc.
4. Khi lập đơn bán, tìm khách bằng ô chọn khách hàng; mã và địa chỉ được điền/hiển thị từ danh mục. Đối chiếu địa chỉ thực tế trước khi gửi đơn.
5. Khi khách không còn giao dịch, tại danh sách chọn **Ngưng dùng** thay vì xóa. Khách ngưng dùng không xuất hiện khi tạo đơn mới; đơn cũ vẫn giữ nguyên lịch sử. Có thể lọc trạng thái và chọn **Kích hoạt** nếu cần dùng lại.

### 3.3. Nhà cung cấp

1. Mở **Kinh doanh → Nhà cung cấp → Thêm mới**.
2. Nhập **Mã NCC** và **Tên NCC**; bổ sung điện thoại, email, địa chỉ, người liên hệ và ghi chú.
3. Chọn **Lưu lại**. Kiểm tra kỹ thông tin trước khi dùng NCC cho đơn mua.
4. Khi ngừng giao dịch với NCC, ở danh sách chọn **Ngưng dùng** thay vì xóa. NCC ngưng dùng không xuất hiện khi lập PO mới; PO và lịch sử nhập/trả cũ vẫn được giữ. Có thể lọc trạng thái rồi chọn **Kích hoạt** để sử dụng lại.

### 3.4. PPCB và hồ sơ liên quan

PPCB là dữ liệu chọn được trên danh mục sản phẩm và dòng đơn bán. Người có quyền quản trị danh mục có thể mở **PPCB** để tạo/sửa hoặc import/export theo menu. Chỉ chọn mã PPCB đã được xác nhận; không tự suy đoán quy trình bào chế từ tên gần giống.

### 3.5. Nguyên liệu thô và phụ liệu

1. Mở **QA → Nguyên liệu thô** hoặc **QA → Phụ liệu**, chọn tạo mới hoặc import theo mẫu do màn hình cung cấp.
2. Kiểm tra mã SKU, tên, đơn vị tính và các thuộc tính danh mục trước khi lưu. Chọn đúng loại danh mục: đơn mua và đơn bán lọc hàng theo loại đã chọn.
3. Sau khi có giao dịch nhập/xuất, xem số tồn tại **Kho → Tồn nguyên liệu thô / phụ liệu**. Không chỉnh số tồn bằng cách sửa danh mục; tồn phát sinh từ các giao dịch và kết luận QC.
4. Phụ liệu hiện được theo dõi tồn tổng, không quản lý số lô NCC/COA như nguyên liệu thô. Nguyên liệu thô cần có số lô, ngày sản xuất, hạn dùng và COA trước khi QC duyệt nhập tồn.

### 3.6. Hồ sơ, tiêu chuẩn và bảo quản sản phẩm

- **SCB/SĐK sản phẩm**: mở **QA → SCB/SĐK sản phẩm**, chọn sản phẩm và quản lý hồ sơ theo màn hình. Tệp hồ sơ gắn với sản phẩm, không thay thế PPCB hoặc BOM.
- **Tiêu chuẩn chất lượng**: mở **QC → Tiêu chuẩn chất lượng** để quản lý tiêu chuẩn của sản phẩm. Chọn đúng sản phẩm và khai báo chỉ tiêu theo tài liệu chất lượng đã được phê duyệt.
- **Phương pháp bảo quản**: mở **QA → Phương pháp bảo quản** để gắn hướng dẫn bảo quản cho sản phẩm. Đây là dữ liệu danh mục có thể được dùng trên nhãn/tài liệu.
- Chỉ nhập tài liệu và chỉ tiêu đã được bộ phận chất lượng/pháp chế xác nhận; giao diện không thay thế SOP và hồ sơ pháp lý gốc.

## 4. Mua hàng và nhận hàng

### 4.1. Lập đơn mua nguyên liệu thô/phụ liệu (Kinh doanh)

1. Mở **Kinh doanh → Đơn mua hàng**, chọn tạo đơn mới.
2. Chọn **Loại hàng đặt mua** là **Nguyên liệu thô** hoặc **Phụ liệu**, nhập mã PO, nhà cung cấp, ngày đặt và ngày giao dự kiến.
3. Thêm từng dòng từ danh mục tương ứng. Chọn hàng hóa, nhập số lượng và đơn giá; đơn vị lấy theo danh mục. Không trộn hai loại hàng trong cùng PO.
4. Nhập ghi chú nếu cần, kiểm tra tiền hàng, thuế và tổng thanh toán.
5. Lưu PO, mở lại chi tiết, rà soát rồi gửi duyệt.
6. Theo dõi trạng thái ở danh sách đơn mua. Nếu bị từ chối, đọc lý do, sửa đơn (trạng thái nháp/từ chối hoặc khi có quyền sửa đơn khóa) và gửi duyệt lại.

### 4.2. Duyệt đơn mua (Ban Giám Đốc)

1. Mở **Ban Giám Đốc → Duyệt đơn mua**. Có thể lọc danh sách trạng thái **Chờ duyệt**.
2. Mở mã PO và kiểm tra NCC, ngày đặt/giao, từng sản phẩm, số lượng, đơn giá, thuế, tổng tiền và ghi chú.
3. Chọn **Phê Duyệt Đơn** nếu đồng ý.
4. Nếu không đồng ý, mở vùng **Từ Chối**, nhập **Lý do từ chối đơn hàng** (bắt buộc) rồi xác nhận. Người lập đơn cần nhận được lý do để sửa.
5. Duyệt PO chỉ cho phép theo dõi giao hàng; chưa ghi nhận hàng về kho.

### 4.3. Kho xác nhận hàng đến và nhập nguyên liệu/phụ liệu

1. Mở **Kho → Nhận đơn mua (NL thô / phụ liệu)**. Với PO đã duyệt, chọn **Xác nhận đợt giao đến**; thao tác này chuyển PO sang trạng thái hàng đã đến và mở bước nhập kho.
2. Mở nhập kho PO, đối chiếu từng dòng, số lượng PO và **Còn xử lý**. Nhập **SL nhập kho**, **SL trả lại** và ghi chú theo số thực tế.
3. Tổng số nhập và trả của mỗi dòng không được vượt lượng PO còn lại. Có thể xử lý PO qua nhiều đợt; trạng thái PO cập nhật theo lượng đã xử lý.
4. Lưu nhập kho. Hàng nhập được tạo thành lô đang **Chờ QC**, chưa được cộng tồn khả dụng. Với nguyên liệu thô, QA cần bổ sung số lô NCC, NSX/HSD và COA trước khi QC có thể duyệt. Phụ liệu không cần số lô/COA.

> Đây là luồng cho nguyên liệu thô/phụ liệu. Luồng phiếu nhận dược liệu cũ (nếu còn được bật trong môi trường triển khai) là màn hình riêng; không dùng các bước trên để kết luận trạng thái lô dược liệu.

### 4.4. Trả hàng nhà cung cấp (nguyên liệu/phụ liệu)

1. Mở **Kho → Trả hàng nhà cung cấp** và tìm dòng PO đã nhập.
2. Chọn đúng lô/đợt nhập còn tồn để trả. Với nguyên liệu thô chọn số lô NCC; phụ liệu không quản lý số lô nên chọn theo dòng hàng.
3. Nhập số lượng trả, số phiếu kiểm nghiệm, ngày kiểm nghiệm và lý do. Số trả không được vượt số lượng đã nhận còn trả được hoặc tồn lô hiện có.
4. Lập đơn trả sẽ ghi giảm tồn ngay và tạo mã trả ở trạng thái chờ giao. Sau khi hàng thực sự được giao NCC, dùng **Xác nhận giao trả** để hoàn tất trạng thái.
5. Nếu hàng vừa nhập nhưng QC chưa duyệt hoặc chưa có số lô/COA, trao đổi với QA/QC để xử lý kết luận chất lượng trước; không xem hàng chờ QC là tồn khả dụng.

## 5. Kiểm tra chất lượng lô nhà cung cấp

### 5.1. COA và quyết định QC

1. Với nguyên liệu thô/phụ liệu mới, dùng **QC → Kiểm tra lô NCC / Kiểm tra phụ liệu** theo mục 5.3. Đây là luồng nhập PO mới.
2. Nếu môi trường còn dùng màn hình **QA → Kiểm tra lô NCC** (`qa.batches`), màn hình đó là luồng lô dược liệu cũ. Tìm theo lô/sản phẩm, kiểm tra COA và lượng khả dụng theo chính giao diện đang triển khai.
3. Lô nguyên liệu mới chỉ cộng tồn sau khi QC xác nhận đạt ở mục 5.3. Với luồng dược liệu cũ, kiểm tra trạng thái và hướng dẫn trên màn hình; không áp quy tắc/mã trạng thái của hai loại lô qua lại.
4. Trả NCC cho raw materials/accessories thực hiện tại **Kho → Trả hàng nhà cung cấp** (mục 4.4). Không ghi nhận một phiếu trả chỉ bằng cách thay trạng thái lô.
5. Tệp COA/PKN trong hệ thống là tài liệu riêng tư, chỉ mở sau đăng nhập và khi tài khoản có quyền phù hợp.

### 5.2. PKN cho lô nội bộ

1. Mở **QC → Phiếu kiểm nghiệm (PKN)**, tìm lô nội bộ đang chờ cập nhật.
2. Đối chiếu sản phẩm, mã lô, NSX/HSD, giấy phép, tồn và lượng chờ nhập.
3. Nhập **Số PKN** và **Ngày ra phiếu**, tải lên tệp PKN dạng PDF/JPG/PNG.
4. Chọn **Ghi nhận PKN đạt** hoặc **Ghi nhận PKN không đạt** đúng theo kết quả phê duyệt; không chọn thay cho kết quả thử nghiệm.
5. Tệp PKN và trạng thái sẽ gắn với lô để tra cứu. Một số luồng nhập kho có thể cho nhập trước khi PKN hoàn tất; điều đó không đồng nghĩa lô đã đạt hoặc được phép giao/bán.

### 5.3. QC nguyên liệu thô và phụ liệu

1. Mở **QC → Kiểm tra lô NCC** hoặc **QC → Kiểm tra phụ liệu**.
2. Với nguyên liệu thô đang chờ QC, QA mở thông tin lô, nhập **Số lô NCC**, **NSX**, **HSD** và tải COA PDF. Lô không thể được QC duyệt nếu còn thiếu các thông tin bắt buộc hoặc COA.
3. Với phụ liệu, lô được theo dõi tổng hợp và không có số lô/COA trong hệ thống.
4. QC chọn **QC xác nhận đạt** để ghi nhận giao dịch nhập tồn. Nếu không đạt, nhập lý do và kết luận **Không đạt**; lượng đó không được cộng vào tồn.
5. Sau khi duyệt, kiểm tra tại **Kho → Tồn nguyên liệu thô / phụ liệu**. Lưu ý màn hình COA chỉ cho người dùng có quyền phù hợp và phải đăng nhập.

## 6. Lập đơn bán và xử lý đơn hàng

### 6.1. Lập đơn bán (Kinh doanh)

1. Mở **Kinh doanh → Đơn bán hàng**, chọn **Lên đơn mới**.
2. Nhập **Mã đơn hàng**, chọn khách hàng; kiểm tra mã và địa chỉ khách hàng tự hiển thị.
3. Chọn **Loại đơn hàng**: **Dược liệu**, **Vị thuốc** hoặc **Nguyên Liệu Thô**; nhập ngày nhận đơn, ngày dự kiến giao, tỉnh/thành và thông tin liên hệ.
4. Với đơn Dược liệu/Vị thuốc, thêm sản phẩm, nhập **Số lượng**, **QCĐG**, chọn PPCB nếu áp dụng và ghi chú. Với đơn Nguyên Liệu Thô, danh sách hàng lấy từ catalog nguyên liệu thô; không áp dụng PPCB/QCĐG thành phẩm.
5. Thành phẩm dự kiến được tính từ số lượng yêu cầu và QCĐG khi có giá trị QCĐG hợp lệ. Rà soát kết quả từng dòng, đơn vị, lô/ghi chú và ngày giao.
6. Chọn **Lưu Đơn Hàng Mới**. Mở chi tiết đơn và gửi/đưa đơn qua phê duyệt theo nút và trạng thái được hiển thị.
7. Danh sách đơn hỗ trợ tìm theo mã đơn, khách hàng hoặc số lô và lọc theo trạng thái. Các trạng thái thường gặp: chờ duyệt, chờ Kho xác nhận tồn, chờ QA chốt lô, chờ hoạch định, chờ sản xuất, chờ Kho nhập lô thành phẩm, chờ xuất giao và hoàn thành.

### 6.2. Duyệt đơn bán (Ban Giám Đốc)

1. Mở **Ban Giám Đốc → Duyệt đơn bán**.
2. Mở hồ sơ, xác nhận khách hàng, sản phẩm, số lượng, QCĐG, thành phẩm dự kiến, hạn giao và ghi chú.
3. Duyệt nếu thông tin phù hợp hoặc từ chối theo chức năng trên màn hình. Khi trả lại, nêu lý do rõ để Kinh doanh sửa.
4. Sau duyệt, đơn đi tiếp đến bước Kho xác nhận tồn; việc duyệt không tự động giữ/trừ hàng trong kho.

### 6.3. Kho xác nhận tồn đơn dược liệu/vị thuốc

1. Mở **Kho → Kiểm tra tồn đơn bán**. Tìm đơn theo mã, khách hàng hoặc sản phẩm.
2. Xem số lượng cần và tồn khả dụng theo từng lô. Kiểm tra mã lô, hạn dùng và lượng đã giữ.
3. Nhập **Kho xác nhận** cho từng sản phẩm theo tồn thực tế đã đối chiếu; số xác nhận không được âm.
4. Chọn **Lưu xác nhận, chuyển QA**. Nếu thiếu hàng, ghi nhận đúng lượng hiện có; đơn vẫn chuyển QA để đánh giá phân bổ lô/sản xuất.
5. Bước này chỉ lưu xác nhận tồn, **không giữ cũng không trừ tồn kho**.

> Đơn loại **Nguyên Liệu Thô** đi theo mục 6.5 sau khi được duyệt, không qua bước kiểm tra tồn đơn thành phẩm này.

### 6.4. QA chốt lô đơn bán

1. Mở chi tiết đơn đang chờ QA từ danh sách đơn bán.
2. Đối chiếu nhu cầu từng sản phẩm, lượng Kho xác nhận và các lô QC đạt còn khả dụng. Chỉ chọn lô phù hợp với sản phẩm, hạn dùng và hồ sơ chất lượng.
3. Gán lượng từ lô đã nhập kho hoặc lượng dự kiến của lô QA vừa tạo độc lập. Lượng đang chờ Kho nhập vẫn được tính vào khả năng phân bổ và được giữ cho đơn; không được phân bổ vượt lượng QA khai báo.
4. Có thể chốt phân bổ dù tổng lô được giữ chưa đủ. Phần thiếu được đưa sang kế hoạch sản xuất; QA có thể tạo thêm mã lô và lượng dự kiến độc lập để phân bổ cho đơn, không lấy lượng từ sản lượng hoàn thành hoặc gắn mã lô vào lệnh.
5. Nếu đã giữ đủ nhu cầu bằng lô đã nhập và lô QA vừa tạo, đơn chuyển sang **Chờ Kho nhập lô thành phẩm**. Chưa thể đóng gói hoặc giao dựa trên lượng đang chờ nhập; Kho phải nhập đủ lượng đã giữ trước.
6. Kiểm tra trạng thái và số lượng đã gán sau khi lưu. Không gán lô NCC đang chờ QC, bị từ chối hoặc không khả dụng.

### 6.5. Đơn bán nguyên liệu thô

1. Kinh doanh tạo đơn bán như bình thường, nhưng chọn loại **Nguyên Liệu Thô**. Danh sách hàng sẽ tìm trong danh mục nguyên liệu thô, không phải danh mục sản phẩm dược liệu.
2. Gửi đơn qua duyệt bán. Sau khi được duyệt, đơn chuyển đến **Kho → Xuất đơn bán nguyên liệu thô**; không đi qua phân bổ lô thành phẩm, đóng gói sản xuất hay kế hoạch sản xuất.
3. Kho kiểm tra đủ tồn và chọn số lượng theo từng lô NCC. Tổng số xuất ở mỗi dòng phải bằng số lượng đơn; không vượt tồn của bất kỳ lô nào.
4. Chọn **Xuất kho** để ghi giao dịch xuất bán. Hệ thống trừ tồn theo các lô và hoàn tất đơn. Nếu tồn không đủ, không thể xuất đủ đơn bằng thao tác này.

## 7. BOM, MRP và duyệt kế hoạch

### 7.1. Tạo BOM (Kế hoạch/người được phân quyền)

1. Mở **Kế hoạch → Định mức sản xuất (BOM)** (mục có thể bị ẩn khỏi sidebar tùy tài khoản), chọn **Tạo phiên bản BOM**.
2. Chọn **Thành phẩm**, nhập **Sản lượng định mức**, **Đơn vị đầu ra**, hệ số đổi về đơn vị gốc và **Tỷ lệ thu hồi (%)**. Ghi chú nếu cần.
3. Trong bảng nguyên liệu, giữ ít nhất một dòng; chọn nguyên liệu, nhập lượng tiêu hao theo sản lượng định mức, đơn vị và hệ số quy đổi về đơn vị gốc. Bấm **Thêm nguyên liệu** để thêm dòng, **Xóa** để bỏ dòng không dùng.
4. Kiểm tra thành phẩm/nguyên liệu không bị chọn nhầm và các đơn vị/quy đổi nhất quán; chọn **Lưu phiên bản BOM**.
5. BOM mới ở trạng thái chờ duyệt; chưa được xem là định mức đang áp dụng cho đến khi người có quyền duyệt xác nhận. Tạo BOM cần quyền Kế hoạch hoặc IT; duyệt dùng quyền duyệt BOM riêng.

### 7.2. Duyệt BOM (Ban Giám Đốc)

1. Mở danh sách **BOM**. Controller cho phép IT, Tổng Giám đốc/chức danh hoặc role được khai báo quyền duyệt; tuy nhiên sidebar hiện chỉ gắn đường dẫn hàng đợi BOM dưới menu Ban Giám Đốc cho IT. Nếu bạn có quyền mà không thấy mục, nhờ quản trị xác nhận đường dẫn/menu trước khi thao tác.
2. Rà soát phiên bản, trạng thái, sản lượng đầu ra, tỷ lệ thu hồi và từng nguyên liệu/định mức.
3. Chọn **Duyệt BOM** để áp dụng phiên bản đã kiểm tra.
4. Nếu không duyệt, nhập **Lý do từ chối** rồi chọn **Từ chối**. Kế hoạch sửa/tạo phiên bản đúng và gửi lại theo quy trình.
5. Người duyệt BOM không mặc nhiên có quyền quản lý/lập BOM; các quyền tạo và duyệt được tách theo tài khoản.

### 7.3. Tính nhu cầu nguyên liệu (MRP)

1. Mở **Kế hoạch → Nhu cầu nguyên liệu** hoặc từ danh sách BOM chọn **Tính nhu cầu MRP**.
2. Chọn thành phẩm và nhập số lượng cần sản xuất theo đơn vị gốc.
3. Chọn **Tính nguyên liệu**. Đọc BOM phiên bản đang được áp dụng và bảng nguyên liệu cần chuẩn bị.
4. MRP chỉ là phép tính/kế hoạch; **không giữ hoặc trừ tồn kho**. Dùng số liệu để đối chiếu kế hoạch, không thay cho phiếu xuất thực tế.

### 7.4. Lập và duyệt kế hoạch theo đơn bán

1. QA chốt phân bổ lô cho đơn. Kế hoạch mở **Kế hoạch → Đơn chờ kế hoạch**.
2. Mỗi sản phẩm hiển thị nhu cầu, lượng đã có lô và lượng còn thiếu. Nhập **Sản lượng sản xuất dự kiến** cho phần thiếu; giá trị không thấp hơn phần thiếu đang hiển thị.
3. Chọn **Lập kế hoạch / lệnh**. Hệ thống tạo kế hoạch/lệnh và nguyên liệu gợi ý theo BOM.
4. Người có quyền mở **Ban Giám Đốc → Duyệt kế hoạch**, kiểm tra đơn, ngày giao, lệnh, số lượng và nguyên liệu gợi ý.
5. Chọn **Duyệt kế hoạch** để mở bước xuất nguyên liệu cho Kho. Nếu cần trả lại, nhập **Lý do trả kế hoạch** rồi chọn **Trả lại**.

### 7.5. Kế hoạch sản xuất theo tháng

1. Kế hoạch mở **Kế hoạch → Kế hoạch sản xuất tháng**, chọn tháng và thêm một dòng cho mỗi sản phẩm.
2. Nhập sản lượng dự kiến, chọn PPCB nếu phù hợp và ghi chú nếu cần. Hiện tại sản lượng do Kế hoạch nhập thủ công; các dòng kế hoạch tháng không còn bắt buộc phải gắn BOM. Không nhầm PPCB với BOM.
3. Lưu dự thảo, rà soát rồi chọn **Gửi duyệt**. Mỗi tháng chỉ có một kế hoạch; mở kế hoạch hiện có để sửa thay vì tạo trùng.
4. Người có quyền mở **Ban Giám Đốc → Duyệt kế hoạch tháng**, xem tháng, sản phẩm, sản lượng và PPCB (nếu có); chọn duyệt hoặc trả lại với lý do.
5. Khi duyệt, hệ thống tạo các lệnh sản xuất độc lập với đơn bán và không cấp trước mã lô thành phẩm. Lệnh hiển thị nguồn là kế hoạch tháng.
6. Gợi ý sản lượng tự động dựa trên lịch sử đơn bán là chức năng dự kiến phát triển sau; hiện tại hệ thống không tự tính hoặc tự thay đổi sản lượng đã nhập.

## 8. Lệnh sản xuất và nhập thành phẩm

### 8.1. Kho xuất nguyên liệu

1. Mở **Kho → Xuất nguyên liệu** (danh sách lệnh sản xuất), tìm lệnh đã được duyệt và mở lệnh.
2. Đọc sản phẩm, nguồn kế hoạch/đơn bán, lượng kế hoạch và lượng nguyên liệu gợi ý theo BOM. Lệnh không có mã lô thành phẩm trước.
3. Với thành phần là sản phẩm/dược liệu, chọn lô NCC đạt QC còn khả dụng. Với nguyên liệu thô/phụ liệu catalog, chọn loại, mặt hàng và lô tồn tương ứng. Nhập lượng xuất cho từng lô; không vượt khả dụng của lô đã chọn.
4. Kiểm tra tổng lượng theo từng nguyên liệu và lô, sau đó chọn **Lập phiếu xuất và trừ kho**. Đây là giao dịch xuất kho thực tế.
5. Nếu thiếu tồn ở bất kỳ nguồn nào, dừng thao tác và phối hợp QA/Kế hoạch; không chọn mặt hàng khác hoặc lô chưa được QC chấp nhận. Xuất theo lệnh ghi nhận ledger tương ứng với loại hàng.

### 8.2. Sản xuất chốt sản lượng và trả nguyên liệu

1. Sau khi sản xuất xong, mở lệnh ở trạng thái đã xuất nguyên liệu.
2. Nhập **Sản lượng thực tế Sản xuất**, **Ngày sản xuất** và lượng nguyên liệu chưa dùng trả kho theo từng dòng/lô đã xuất.
3. Số lượng trả không vượt lượng đã xuất từ lô đó. Lượng tiêu hao theo truy xuất được xác định bằng lượng xuất trừ lượng trả.
4. Chọn **Chốt sản lượng sản xuất**. Kiểm tra trạng thái lệnh và lượng thành phẩm chờ nhập.
5. Sau khi chốt, sản lượng thực tế và phần sản lượng còn theo dõi được ghi trên lệnh sản xuất. Đây là số liệu sản xuất riêng, không tự tạo lô hoặc quyết định lượng lô QA khai báo.

### 8.3. QA tạo mã lô nội bộ và cập nhật PKN

1. Mở **QA → Quản lý lô nội bộ → Tạo lô**. Không cần chọn lệnh sản xuất hoặc đối chiếu lô với lượng hoàn thành của lệnh.
2. Chọn sản phẩm, nhập mã lô, **số lượng dự kiến có thể phân bổ/nhập kho**, ngày sản xuất/hạn dùng và thông tin cần thiết. Hệ thống tạo lô độc lập, ghi lượng dự kiến là lượng chờ Kho nhập.
3. Mở đơn đang chờ QA và phân bổ lượng từ lô vừa tạo. Hệ thống giữ lượng đã phân bổ để không dùng trùng cho đơn khác; đơn có thể được phân bổ trước khi Kho nhập.
4. Lượng dự kiến của lô do QA khai báo, không tự lấy theo sản lượng hoàn thành. Nếu cần thêm lô, tạo mã lô mới và nhập lượng dự kiến riêng.
5. Sau khi lô đã phân bổ, đơn chờ Kho nhập đủ lượng đã giữ rồi mới chuyển sang đóng gói. Kho nhập số lượng thực tế theo từng lần, không vượt lượng dự kiến; sau khi nhập kho, mở **QC → Phiếu kiểm nghiệm (PKN)** để bổ sung PKN theo mục 5.2.

### 8.4. Kho nhập thành phẩm

1. Mở **Kho → Nhập thành phẩm**. Tìm lô đang chờ nhập theo mã lô, sản phẩm hoặc lệnh (nếu có).
2. Kiểm tra mã lô, nguồn lô, lượng dự kiến, lượng đã nhập và lượng còn chờ.
3. Nhập **SL nhận thực tế** không vượt lượng dự kiến QA đã khai báo và lượng còn chờ, rồi chọn **Nhập kho**. Không cần đợi đơn bán đóng gói để nhập kho.
4. Có thể nhập thành nhiều lần cho đến khi nhận đủ. Khi Kho đã nhập đủ lượng lô đang giữ cho đơn, hệ thống tự chuyển đơn sang trạng thái chờ đóng gói/giao.
5. PKN đang chờ không nhất thiết chặn thao tác nhập, nhưng lô chưa đạt QC không được coi là đã đạt để xuất hàng.

## 9. Nhãn, đóng gói và giao hàng

### 9.1. In nhãn theo lô đơn hàng (Sản xuất)

1. Mở **Sản xuất → In nhãn theo lô đơn hàng**, chọn đơn cần xử lý.
2. Đối chiếu sản phẩm, QCĐG, lô QA chỉ định, trạng thái QA, thành phẩm, số tem chuẩn, số đã in và số còn phải in.
3. Nhập số tem cần in theo từng lô (tối đa 1.000 tem/lô mỗi lần), **Tiêu chuẩn chất lượng** và **Bảo quản** nếu yêu cầu trên biểu mẫu.
4. Nếu in vượt số tem chuẩn/còn lại, ghi **Lý do in bổ sung**.
5. Chọn **In trực tiếp** để mở bản xem trước. Chỉ bấm nút **In** trong bản xem trước để mở hộp thoại máy in và ghi nhận lượt in.
6. Hoặc chọn **Tải mẫu Excel để in** nếu quy trình sử dụng mẫu Excel. Kiểm tra số in ghi nhận sau khi hoàn tất.

### 9.2. Sản xuất xác nhận đóng gói

1. Mở **Sản xuất → Xác nhận đóng gói**.
2. Chỉ xác nhận khi QA đã gán đủ lô và nhãn đã được in. Đối chiếu đơn, sản phẩm, lô đã giữ và số nhãn.
3. Chọn **In / xem nhãn** nếu cần kiểm tra, rồi chọn **Xác nhận đơn đã đóng gói**.
4. Xác nhận đóng gói không tự gửi hàng và chưa trừ tồn kho.

### 9.3. Kho đóng hàng và xác nhận gửi

1. Mở **Kho → Đóng gói và xuất hàng**, mở đơn đã đóng gói.
2. Nếu chưa đóng, đối chiếu lô đã giữ, số lượng theo đơn và thành phẩm dự kiến. Nhập **SL đóng hàng đợt này** theo từng sản phẩm, không vượt lượng còn có thể xuất/được giữ.
3. Chọn **Xác nhận đóng hàng**. Có thể giao nhiều đợt; phần chưa giao còn lại để tiếp tục xử lý.
4. Khi đơn hiển thị hàng đã đóng, kiểm tra số lượng đóng và thông tin giao nhận, sau đó chọn **Xác nhận đã gửi hàng**.
5. Xác nhận gửi hàng là thời điểm ghi nhận giao dịch **SALE_SHIPMENT** và trừ tồn kho. Không bấm trước khi hàng thực sự được gửi đi.

## 10. Tồn kho, truy xuất và quản trị

### 10.1. Kiểm tra tồn

Mở **Kho → Kiểm tra tồn kho tổng** để xem số liệu kho. Đối chiếu tồn thực tế, trạng thái QC, lượng đã giữ và lượng khả dụng theo lô. Tồn khả dụng có thể thấp hơn tồn thực tế vì đã giữ cho đơn khác; không dùng tổng tồn để hứa giao khi chưa kiểm tra lượng có thể phân bổ.

### 10.1.1. Tồn nguyên liệu thô/phụ liệu

Mở **Kho → Tồn nguyên liệu thô / phụ liệu**, lọc loại hàng hoặc tìm theo tên/SKU. Màn hình này tổng hợp giao dịch nhập, xuất và số dư của catalog vật tư; riêng nguyên liệu thô có thể xem số dư theo lô. Chỉ lô đã được QC xác nhận đạt mới tạo giao dịch nhập tồn khả dụng. Phụ liệu không có tồn theo số lô.

### 10.2. Tra cứu nguồn gốc lô

1. Mở **Truy xuất nguồn gốc → Tra cứu lô và chuỗi liên kết**.
2. Nhập số lô NCC, lô nội bộ hoặc lô thành phẩm, chọn **Tra cứu**.
3. Kiểm tra sản phẩm, tồn, trạng thái, ngày sản xuất/hạn dùng, PKN (nếu có), lệnh sản xuất, đơn bán và các bước giao hàng được liên kết.
4. Nếu không tìm thấy, kiểm tra lại mã lô chính xác; lô nội bộ có thể có lịch sử mã dự trù → mã hiện tại.

### 10.3. Quản lý mã truy xuất

Đây là chức năng quản trị có quyền hạn chế; menu có thể xuất hiện với QA/IT được cấp quyền.

1. Mở **Truy xuất nguồn gốc → Quản lý mã truy xuất**.
2. Cấu hình **Đường dẫn truy xuất cố định** (URL hợp lệ) rồi lưu. Hệ thống nối đường dẫn với chuỗi truy xuất từng lô để tạo liên kết QR.
3. Lọc loại lô (tất cả/lô NCC/lô nội bộ); tìm đúng mã lô và sửa **Chuỗi truy xuất** nếu được phân công.
4. Xem liên kết QR sinh ra trước khi đưa tem/tài liệu ra sử dụng. Chỉ xóa mã khi có phê duyệt theo quy trình nội bộ.

### 10.4. Tài khoản, phòng ban và hồ sơ cá nhân

- IT mở **Quản trị → Nhân sự** để xem và quản lý hồ sơ nhân viên; danh sách này không hiển thị cho vai trò khác. Người có quyền quản trị mở **Phòng ban** để quản lý dữ liệu tương ứng; một số danh sách hỗ trợ import/export. Gán phòng ban, vai trò và trạng thái làm việc theo phê duyệt nội bộ. Người dùng không tự đăng ký tài khoản.
- Người dùng mở hồ sơ cá nhân để cập nhật thông tin của mình hoặc đổi mật khẩu. Không chia sẻ mật khẩu hay dùng chung tài khoản vì các giao dịch được ghi nhận theo người thao tác.
- **IT là vai trò hỗ trợ kỹ thuật, không phải vai trò nghiệp vụ/phê duyệt.** Quyền hệ thống cao cần được sử dụng theo ủy quyền, đúng mục đích hỗ trợ và tuân thủ lưu vết/audit trail của doanh nghiệp. Không dùng đặc quyền IT để tự phê duyệt nghiệp vụ hoặc thay thế người chịu trách nhiệm nghiệp vụ.

## 11. Xử lý tình huống thường gặp

| Tình huống | Cách kiểm tra/xử lý |
|---|---|
| Không thấy menu hoặc nút | Kiểm tra đúng tài khoản/phòng ban; nhờ quản trị xác nhận quyền. Không suy ra rằng hồ sơ không tồn tại chỉ vì menu không hiện. |
| Không tìm thấy mã đơn/lô | Bỏ bộ lọc trạng thái, thử tìm bằng mã chính xác hoặc tên sản phẩm/khách hàng, kiểm tra phân trang. |
| Không gửi/duyệt được hồ sơ | Đọc thông báo lỗi; kiểm tra trường bắt buộc, trạng thái hiện tại và quyền thao tác. Một hồ sơ đã qua bước sau thường không còn nút sửa/gửi ở bước trước. |
| Lô không xuất hiện khi phân bổ | Kiểm tra trạng thái QC, hạn dùng, sản phẩm, tồn khả dụng và lượng đã giữ. Lô chờ QC/không đạt/hết hạn không thể dùng như lô đạt. |
| Không thể nhận PO đủ số lượng | Xem lượng còn xử lý. Ghi số nhận thực tế theo đợt; không vượt phần còn lại. Lập phiếu nhận sau cho phần nhà cung cấp giao bổ sung. |
| Đơn bán thiếu hàng | Kho ghi lượng xác nhận đúng thực tế; QA phân bổ lượng có sẵn; phần thiếu chuyển Kế hoạch để lập sản xuất theo luồng. |
| Không thấy nguyên liệu để xuất | Kiểm tra BOM gợi ý, kế hoạch đã được duyệt và lô NCC đạt QC còn khả dụng. Liên hệ QA/Kế hoạch để xử lý dữ liệu nguồn. |
| Đã đóng nhưng tồn chưa giảm | Đây là hành vi đúng: đóng gói chưa trừ tồn. Chỉ xác nhận gửi hàng sau khi hàng thực sự rời kho. |
| PKN/COA thiếu hoặc không đạt | Không tự thay trạng thái. Bổ sung hồ sơ, chờ QC xử lý hoặc làm theo quy trình trả hàng; hỏi người phụ trách chất lượng khi chưa rõ. |
| Lưu thất bại/giá trị sai | Sửa trường được báo lỗi, kiểm tra đơn vị và độ chính xác thập phân, tránh nhập dấu phân cách hàng nghìn vào ô số. Gửi lại một lần và xác minh trạng thái trước khi lặp thao tác. |

## 12. Danh sách kiểm tra bàn giao công việc

### Kinh doanh
- [ ] Danh mục khách hàng/NCC chính xác.
- [ ] Đơn có mã, khách hàng/NCC, sản phẩm, số lượng, đơn vị và ngày giao phù hợp.
- [ ] Đã gửi duyệt và theo dõi phản hồi/từ chối.

### Ban Giám Đốc
- [ ] Mở đúng hồ sơ và kiểm tra chi tiết, không duyệt chỉ dựa trên mã/trạng thái.
- [ ] Khi từ chối, ghi lý do đủ rõ để người lập sửa.
- [ ] BOM/plan/đơn mua/đơn bán được duyệt đúng phạm vi ủy quyền.

### QA/QC
- [ ] Hồ sơ COA/PKN và kết quả gắn đúng lô.
- [ ] Lô được chọn đúng sản phẩm, trạng thái và lượng khả dụng.
- [ ] Mã dự trù/mã chính thức và lịch sử mã được kiểm tra.

### Kế hoạch/Sản xuất
- [ ] BOM đã được duyệt, lượng kế hoạch và quy đổi đơn vị được đối chiếu.
- [ ] Chốt sản lượng thực tế và nguyên liệu trả theo từng lô.
- [ ] Nhãn đã in đúng lô; xác nhận đóng gói sau khi kiểm tra.

### Kho
- [ ] Phiếu nhận ghi đúng số thực nhận, trả tại điểm nhận và lý do.
- [ ] Xuất nguyên liệu từ lô đạt QC; không vượt khả dụng.
- [ ] Nhập thành phẩm theo lô và lượng chờ nhận.
- [ ] Chỉ xác nhận gửi hàng khi lô, số lượng và việc gửi thực tế đã được kiểm tra.

---

**Giới hạn tài liệu:** Hướng dẫn được biên soạn theo menu, route và biểu mẫu hiện có trong ứng dụng tại thời điểm rà soát. Quy định chất lượng, hạn mức phê duyệt, chứng từ kế toán, SLA, biểu mẫu ngoài hệ thống và quy trình ngoại lệ cần đối chiếu SOP nội bộ; tài liệu này không tự đặt ra các quy định đó.
