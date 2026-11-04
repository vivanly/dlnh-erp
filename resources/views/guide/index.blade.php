@php
    $u = auth()->user();

    $roles = [
        'sales' => [
            'title' => 'Kinh doanh',
            'mine' => $u->isSalesDepartment(),
            'rights' => 'Quản lý khách hàng, nhà cung cấp; lập và gửi duyệt đơn bán, đơn mua; theo dõi trạng thái và phản hồi từ bước duyệt.',
            'steps' => [
                'Chuẩn bị danh mục: kiểm tra khách hàng, nhà cung cấp, sản phẩm đã có trong hệ thống.',
                'Lập đơn bán / đơn mua ở trạng thái Nháp, kiểm tra mặt hàng, số lượng rồi gửi duyệt.',
                'Theo dõi đơn: nếu bị từ chối, đọc lý do, sửa và gửi lại.',
                'Sau khi được duyệt, theo dõi các bước Kho/QA/Sản xuất đến khi giao hàng.',
            ],
        ],
        'director' => [
            'title' => 'Ban Giám Đốc / người duyệt',
            'mine' => $u->isDirector() || $u->isGeneralDirector(),
            'rights' => 'Duyệt hoặc từ chối đơn mua, đơn bán, BOM, kế hoạch sản xuất/tháng; xem báo cáo tổng quan.',
            'steps' => [
                'Mở Tổng quan hoặc menu duyệt để xem hàng đợi Chờ duyệt.',
                'Mở chi tiết hồ sơ, kiểm tra số liệu rồi chọn Duyệt hoặc Từ chối (kèm lý do).',
                'Kiểm tra lại trạng thái mới sau khi duyệt. Đã duyệt không có nghĩa là đã nhập kho/giao hàng.',
            ],
        ],
        'planning' => [
            'title' => 'Kế hoạch',
            'mine' => $u->isPlanningDepartment(),
            'rights' => 'Tạo BOM, tính nhu cầu nguyên liệu (MRP), lập kế hoạch theo đơn bán và kế hoạch sản xuất tháng.',
            'steps' => [
                'Tạo BOM cho sản phẩm và gửi Ban Giám Đốc duyệt.',
                'Dùng MRP (dựa trên BOM đã duyệt) để tính nhu cầu nguyên liệu.',
                'Lập kế hoạch theo đơn bán hoặc kế hoạch tháng, gửi duyệt.',
                'Sau khi duyệt, lệnh sản xuất được chuyển cho Kho và Sản xuất.',
            ],
        ],
        'warehouse' => [
            'title' => 'Kho',
            'mine' => $u->isWarehouseDepartment(),
            'rights' => 'Nhận hàng và lập phiếu nhập; kiểm tra tồn đơn bán; xuất nguyên liệu; nhập thành phẩm; đóng hàng và xác nhận gửi.',
            'steps' => [
                'Đơn mua đã duyệt: xác nhận hàng đến, lập phiếu nhận (lô chuyển sang Chờ QC).',
                'Đơn bán đã duyệt: kiểm tra tồn và xác nhận.',
                'Lệnh sản xuất: xuất nguyên liệu; khi hoàn tất nhập thành phẩm.',
                'Sau khi Sản xuất xác nhận đóng gói: đóng hàng, rồi chỉ xác nhận gửi khi hàng thực sự rời kho (đây là lúc hệ thống trừ tồn).',
            ],
        ],
        'qa' => [
            'title' => 'QA',
            'mine' => $u->isQADepartment(),
            'rights' => 'Cập nhật số lô NCC/COA; tạo mã lô thành phẩm nội bộ; phân bổ/chốt lô cho đơn bán.',
            'steps' => [
                'Cập nhật số lô nhà cung cấp và COA cho dược liệu thô.',
                'Tạo mã lô nội bộ cho thành phẩm và chốt lô cho đơn bán.',
                'Phối hợp QC để lô đạt mới được sử dụng.',
            ],
        ],
        'qc' => [
            'title' => 'QC',
            'mine' => $u->isQCDepartment(),
            'rights' => 'Kết luận Đạt/Không đạt cho lô dược liệu thô, phụ liệu; cập nhật PKN lô thành phẩm.',
            'steps' => [
                'Mở danh sách lô Chờ QC.',
                'Kiểm tra COA/mẫu, ghi kết luận Đạt hoặc Không đạt.',
                'Cập nhật PKN cho lô thành phẩm nếu thuộc quyền.',
            ],
        ],
        'production' => [
            'title' => 'Sản xuất',
            'mine' => $u->isProductionDepartment(),
            'rights' => 'Xem lệnh sản xuất, chốt sản lượng và nguyên liệu trả; in nhãn; xác nhận đóng gói.',
            'steps' => [
                'Nhận lệnh sản xuất sau khi Kho xuất nguyên liệu.',
                'Sản xuất xong: chốt sản lượng và nguyên liệu trả lại kho.',
                'In nhãn theo lô đơn hàng (nếu được cấp quyền in nhãn).',
                'Xác nhận đóng gói để Kho đóng hàng và gửi.',
            ],
        ],
        'it' => [
            'title' => 'IT / Quản trị kỹ thuật',
            'mine' => $u->isITDepartment(),
            'rights' => 'Tạo/cấp quyền tài khoản, quản lý phòng ban, phân quyền in nhãn, hỗ trợ kỹ thuật. IT không phải bước phê duyệt nghiệp vụ.',
            'steps' => [
                'Tạo tài khoản, gán phòng ban và chức danh đúng.',
                'Quản lý phòng ban, phân quyền in nhãn.',
                'Khi người dùng không thấy menu: kiểm tra phòng ban/chức danh của tài khoản.',
            ],
        ],
    ];

    $flow = [
        ['Chuẩn bị danh mục', 'Sản phẩm, khách hàng, nhà cung cấp, PPCB'],
        ['Mua hàng', 'Kinh doanh lập đơn → Ban Giám Đốc duyệt → Kho nhận'],
        ['Kiểm tra chất lượng', 'QA cập nhật COA → QC kết luận lô'],
        ['Bán hàng', 'Kinh doanh lập đơn → duyệt → Kho kiểm tồn → QA chốt lô'],
        ['BOM, MRP, kế hoạch', 'Kế hoạch lập → Ban Giám Đốc duyệt'],
        ['Sản xuất', 'Kho xuất NL → Sản xuất chốt sản lượng → QA mã lô → Kho nhập TP'],
        ['Nhãn, đóng gói, giao', 'In nhãn → Sản xuất đóng gói → Kho gửi hàng'],
        ['Tồn kho & truy xuất', 'Tra tồn, tra nguồn gốc lô'],
    ];

    $statuses = [
        'Nháp' => 'Chưa gửi duyệt, còn chỉnh sửa được.',
        'Chờ duyệt' => 'Đang trong hàng đợi của người có quyền duyệt.',
        'Bị từ chối' => 'Xem lý do, sửa rồi gửi lại.',
        'Đã duyệt' => 'Được phép làm bước kế tiếp; chưa phải đã nhập kho/giao hàng.',
        'Chờ QC' => 'Lô chưa được dùng cho đến khi QC xác nhận đạt.',
        'Đã đóng gói' => 'Kho ghi nhận đóng hàng; chưa gửi và chưa trừ tồn.',
        'Hoàn thành' => 'Bước cuối đã chốt; kiểm tra số lượng trước khi coi là xong.',
    ];

    $myRoles = collect($roles)->filter(fn ($r) => $r['mine']);
    $otherRoles = collect($roles)->reject(fn ($r) => $r['mine']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Trợ giúp</p>
            <h1 class="mt-0.5 text-lg font-bold tracking-tight text-slate-900">Hướng dẫn sử dụng hệ thống</h1>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <section class="bg-white border border-slate-200 rounded-2xl p-5">
            <p class="text-sm text-slate-500">Tài khoản của bạn</p>
            <p class="text-base font-bold text-slate-900">
                {{ $u->name }}
                <span class="font-normal text-slate-500">— {{ $u->department->name ?? 'Chưa gán phòng ban' }}@if($u->position), {{ $u->position }}@endif</span>
            </p>
            <p class="mt-2 text-sm text-slate-600">
                Quyền hạn dưới đây phụ thuộc phòng ban/chức danh. Nếu không thấy menu cần dùng, hãy liên hệ IT để kiểm tra lại tài khoản.
            </p>
        </section>

        <section class="bg-white border border-slate-200 rounded-2xl p-5">
            <h2 class="text-base font-bold text-slate-900 mb-3">Cách dùng cơ bản</h2>
            <ol class="list-decimal ps-5 space-y-1.5 text-sm text-slate-700">
                <li>Đăng nhập bằng tài khoản cá nhân; chọn <b>Tổng quan</b> để quay về trang chính.</li>
                <li>Dùng thanh menu để mở màn hình công việc; dùng ô tìm kiếm và bộ lọc trạng thái để thu hẹp danh sách.</li>
                <li>Mở chi tiết mã đơn/mã lô/mã lệnh để xem trước khi thao tác.</li>
                <li>Sau khi lưu hoặc duyệt, đọc thông báo và kiểm tra lại trạng thái mới; nếu báo lỗi, sửa trường được nêu rồi gửi lại.</li>
            </ol>
        </section>

        @if ($myRoles->isNotEmpty())
            <section class="space-y-3">
                <h2 class="text-base font-bold text-slate-900">Quyền hạn và việc của bạn</h2>
                @foreach ($myRoles as $role)
                    <div class="bg-blue-50/60 border border-blue-200 rounded-2xl p-5">
                        <h3 class="font-bold text-blue-800">{{ $role['title'] }}</h3>
                        <p class="mt-1 text-sm text-slate-700"><b>Quyền hạn:</b> {{ $role['rights'] }}</p>
                        <ol class="mt-2 list-decimal ps-5 space-y-1 text-sm text-slate-700">
                            @foreach ($role['steps'] as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </section>
        @else
            <section class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-sm text-amber-800">
                Tài khoản chưa được gán phòng ban/chức danh nghiệp vụ. Vui lòng liên hệ IT để được cấp quyền.
            </section>
        @endif

        <section class="bg-white border border-slate-200 rounded-2xl p-5">
            <h2 class="text-base font-bold text-slate-900 mb-3">Quy trình tổng thể</h2>
            <ol class="space-y-2">
                @foreach ($flow as $i => [$name, $desc])
                    <li class="flex gap-3 text-sm">
                        <span class="shrink-0 w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                        <span><b class="text-slate-900">{{ $name }}:</b> <span class="text-slate-600">{{ $desc }}</span></span>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="bg-white border border-slate-200 rounded-2xl p-5">
            <h2 class="text-base font-bold text-slate-900 mb-3">Ý nghĩa các trạng thái</h2>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                @foreach ($statuses as $name => $desc)
                    <div><dt class="inline font-semibold text-slate-900">{{ $name }}:</dt> <dd class="inline text-slate-600">{{ $desc }}</dd></div>
                @endforeach
            </dl>
        </section>

        @if ($otherRoles->isNotEmpty())
            <section class="bg-white border border-slate-200 rounded-2xl p-5">
                <h2 class="text-base font-bold text-slate-900 mb-3">Công việc của các bộ phận khác</h2>
                <div class="space-y-2">
                    @foreach ($otherRoles as $role)
                        <details class="border border-slate-200 rounded-xl px-4 py-2.5">
                            <summary class="cursor-pointer font-semibold text-slate-800">{{ $role['title'] }}</summary>
                            <p class="mt-2 text-sm text-slate-700"><b>Quyền hạn:</b> {{ $role['rights'] }}</p>
                            <ol class="mt-2 list-decimal ps-5 space-y-1 text-sm text-slate-700">
                                @foreach ($role['steps'] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
