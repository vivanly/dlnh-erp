<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Lệnh sản xuất / Import Excel</h2></x-slot>
    <div class="py-3"><div class="max-w-4xl space-y-4">
        @if($errors->any())<div class="border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if(session('warning'))
            <div class="border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                <p>{{ session('warning') }}</p>
                <ul class="mt-2 list-disc pl-5">@foreach(session('import_errors', []) as $failure)<li>Dòng {{ $failure['row'] }}: {{ implode('; ', $failure['messages']) }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('manual-labels.orders.import') }}" enctype="multipart/form-data" class="space-y-4 border border-slate-300 bg-white p-4">
            @csrf
            <label class="block text-xs font-bold">Chọn file Excel (.xlsx, .xls, .csv)
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="mt-1 block w-full border border-slate-300 p-2 text-xs">
            </label>
            <button class="h-8 bg-emerald-700 px-4 text-[11px] font-bold uppercase text-white">Tiến hành import</button>
            <a href="{{ route('manual-labels.orders') }}" class="ml-3 text-xs text-slate-500 underline">Quay lại danh sách</a>
        </form>
        <div class="border border-slate-300 bg-white p-4 text-xs text-slate-600 space-y-2">
            <p>Dùng dòng đầu làm tiêu đề. Có thể dùng file export từ danh sách lệnh.</p>
            <p>Cột bắt buộc: <code>production_order</code> (lệnh SX), <code>name</code> (tên hàng), <code>batch</code> (số lô), <code>pack_spec</code> (QCĐG), <code>quantity</code> (số lượng), <code>label_type</code> (vt, dl_n hoặc dl_b; để trống để xác định theo mã hàng).</p>
            <p>Cột tùy chọn: <code>order_date</code>, <code>mfg_date</code>, <code>exp_date</code>, <code>traditional_medicine_name</code> (0 hoặc 1), <code>qr_content</code>, <code>storage</code>. Ngày nhập dd/mm/yyyy, yyyy-mm-dd hoặc ô ngày Excel. Cần điền ngày lệnh để dòng được chọn khi export theo thời gian.</p>
            <p>Dòng trùng lệnh SX + tên hàng + số lô sẽ được bỏ qua, không cập nhật dữ liệu cũ. Mã nhãn tự sinh. Tối đa 10 lỗi đầu tiên được hiển thị.</p>
        </div>
    </div></div>
</x-app-layout>
