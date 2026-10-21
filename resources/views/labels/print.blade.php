@php
    $templates = [
        'vt' => [
            'title' => 'NGUYÊN LIỆU LÀM THUỐC - VỊ THUỐC CỔ TRUYỀN',
            'name' => 'Tên vị thuốc:',
            'registration' => 'SĐK/SCB:',
            'font' => '"Times New Roman", Times, serif',
            'size' => 10,
            'titleEnd' => 17,
            'registrationEnd' => 6,
            'rows' => [13.05, 13.95, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 7.95, 13.05, 13.95, 13.95, 13.95, 13.95, 13.95, 12, 12],
        ],
        'dl_n' => [
            'title' => 'NGUYÊN LIỆU LÀM THUỐC - DƯỢC LIỆU TRONG NƯỚC',
            'name' => 'Tên dược liệu:',
            'registration' => 'SĐK/SCB:',
            'font' => 'Arial, Helvetica, sans-serif',
            'size' => 9.2,
            'titleEnd' => 18,
            'registrationEnd' => 6,
            'rows' => [14.4, 9.3, 12, 12, 12, 12, 12, 12, 12, 12, 14.4, 11.4, 11.4, 14.4, 15, 13.95, 13.95, 13.95, 13.95, 13.95, 13.95],
        ],
        'dl_b' => [
            'title' => 'NGUYÊN LIỆU LÀM THUỐC - DƯỢC LIỆU NHẬP KHẨU',
            'name' => 'Tên dược liệu:',
            'registration' => 'SĐK/SCB/Số GPNK:',
            'font' => 'Arial, Helvetica, sans-serif',
            'size' => 9.2,
            'titleEnd' => 18,
            'registrationEnd' => 7,
            'rows' => [14.4, 9.3, 12, 12, 12, 12, 12, 12, 12, 12, 14.4, 11.4, 11.4, 14.4, 15, 13.95, 13.95, 13.95, 13.95, 13.95, 13.95],
        ],
    ];
    // Độ rộng cột C..V theo mẫu Nhan.xlsx.
    $columns = [
        'vt' => [4.78, 4.78, 4, 4, 4, 3.56, 3.56, 3.56, 3.56, 3.56, 3.56, 3.56, 3.56, 2.78, 2.78, 3, 3, 3, 3, 3],
        'dl_n' => [5.11, 5.11, 4, 4, 4, 3.56, 3.56, 3.56, 3.56, 3.56, 3.56, 3.56, 3.56, 2.78, 2.78, 2.89, 2.89, 2.89, 2.89, 2.89],
        'dl_b' => [5.11, 5.11, 4, 4, 4, 4.11, 3.56, 3.56, 3.56, 3.56, 3.56, 3.56, 3.56, 2.78, 2.78, 2.78, 2.78, 2.78, 2.78, 2.78],
    ];
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>In nhãn {{ $order->order_code }}</title>
    @vite('resources/js/app.js')
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page { width: 293mm; height: 206mm; margin: 2mm auto; display: grid; grid-template-columns: 144.5mm 144.5mm; grid-template-rows: 101mm 101mm; column-gap: 4mm; row-gap: 4mm; align-content: start; overflow: hidden; break-after: page; page-break-after: always; }
        .page:last-child { break-after: auto; page-break-after: auto; }
        .slot { width: 144.5mm; height: 101mm; }
        .label { position: relative; width: 144.5mm; height: 101mm; display: grid; border: 1mm double #000; line-height: 1.12; overflow: hidden; }
        .label > div { min-width: 0; min-height: 0; overflow: hidden; padding: .25mm .7mm; }
        .watermark { position: absolute; inset: 0; z-index: 0; display: flex; align-items: center; justify-content: center; padding: 0 !important; pointer-events: none; }
        .watermark img { width: 100%; height: 100%; object-fit: fill; opacity: .12; transform: translateY(-1mm); }
        .label > div:not(.watermark) { position: relative; z-index: 1; }
        .box-top { border-top: .25mm solid #000; }
        .box-v { border-left: .25mm solid #000; }
        .lot-divider { position: relative; }
        .lot-divider::before { content: ""; position: absolute; top: 3mm; bottom: 0; left: 0; border-left: .15mm solid #aaa; }
        .lot-top-rule { position: relative; }
        .lot-top-rule::before { content: ""; position: absolute; top: 3mm; left: 0; right: 0; border-top: .15mm solid #aaa; }
        .lot-box-rule { border-top: .15mm solid #aaa; }
        .lot-info-stack { display: flex; flex-direction: column; justify-content: space-between; padding-top: 3.8mm !important; padding-bottom: 1mm !important; }
        .lot-info-row { display: grid; grid-template-columns: 3fr 9fr; align-items: center; line-height: 1; }
        .quality-block { display: flex; flex-direction: column; justify-content: center; gap: 1mm; line-height: 1.05; }
        .product-name { display: flex; align-items: center; white-space: nowrap; overflow: hidden; }
        .batch-value { display: block; min-width: 0; white-space: nowrap; overflow: hidden; }
        .fit-one-line { white-space: nowrap; }
        .fit-wrap { white-space: normal; overflow-wrap: anywhere; word-break: normal; }
        .packaging-control-block { display: flex; flex-direction: column; align-items: center; justify-content: space-evenly; padding: .7mm !important; }
        .packaging-control-line { display: flex; flex-wrap: nowrap; align-items: center; justify-content: center; gap: 2mm; width: 100%; white-space: nowrap !important; }
        .packaging-control-line > span { flex: 0 0 auto; white-space: nowrap !important; }
        .scientific-name { overflow-wrap: anywhere; word-break: normal; line-height: 1.05; }
        .field-label-nowrap { white-space: nowrap; font-size: .9em; }
        .b { font-weight: bold; }
        .i { font-style: italic; }
        .center { text-align: center; }
        .mid { display: flex; align-items: center; justify-content: center; }
        .logo { display: flex; align-items: center; justify-content: center; padding: .6mm !important; }
        .logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .qr-box { grid-column: 14 / 21; grid-row: 16 / 20; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 0 !important; }
        .qr-box canvas { display: block; width: 18mm !important; height: 18mm !important; max-width: 100%; max-height: 100%; }
        .qr-heading { padding-top: 1.2mm !important; line-height: 1 !important; }
        .rule-bottom { border-bottom: .25mm solid #000; }
    </style>
</head>
<body>
@foreach (collect($labels)->chunk(4) as $pageLabels)
    <section class="page">
        @foreach ($pageLabels as $label)
            @php
                $type = isset($templates[$label['label_type']]) ? $label['label_type'] : 'dl_n';
                $t = $templates[$type];
                $rows = $t['rows'];
                $cols = $columns[$type];
                $gridColumns = implode(' ', array_map(fn ($w) => $w . 'fr', $cols));
                $gridRows = implode(' ', array_map(fn ($h) => $h . 'fr', $rows));
                $registrationValueStart = $t['registrationEnd'];
                $productName = mb_strtoupper($label['product'], 'UTF-8');
                $productNameLength = mb_strlen($productName, 'UTF-8');
                $productFontSize = $productNameLength > 26 ? max(6, $t['size'] * 26 / $productNameLength) : $t['size'];
            @endphp
            <div class="slot">
                <div class="label" style="grid-template-columns: {{ $gridColumns }}; grid-template-rows: {{ $gridRows }}; font-family: {{ $t['font'] }}; font-size: {{ $t['size'] }}pt;">
                    @if(!empty($watermark))
                        @php $watermarkPath = $type === 'vt' ? 'images/watermark-vt.svg' : 'images/watermark-herb.svg'; @endphp
                        <div class="watermark"><img src="{{ asset($watermarkPath) }}?v={{ filemtime(public_path($watermarkPath)) }}" alt=""></div>
                    @endif
                    {{-- Đường kẻ và khung --}}
                    <div class="lot-divider" style="grid-column: 14 / 21; grid-row: 4 / 20; padding: 0;"></div>
                    <div class="lot-top-rule" style="grid-column: 14 / 21; grid-row: 4 / 5; padding: 0;"></div>
                    <div class="lot-box-rule" style="grid-column: 14 / 21; grid-row: 11 / 12; padding: 0;"></div>
                    <div class="lot-box-rule" style="grid-column: 14 / 21; grid-row: 14 / 15; padding: 0;"></div>
                    <div class="lot-box-rule" style="grid-column: 14 / 21; grid-row: 16 / 17; padding: 0;"></div>
                    <div style="grid-column: 1 / 14; grid-row: 13 / 14; padding: 0; position: relative;"><span style="position: absolute; left: 6mm; right: 6mm; bottom: 0; border-bottom: .25mm solid #000;"></span></div>
                    <div class="box-top" style="grid-column: 1 / 21; grid-row: 20 / 22; padding: 0;"></div>

                    <div class="logo" style="grid-column: 1 / 3; grid-row: 1 / 6;"><img src="{{ asset('images/label-logo.png') }}" alt="Logo"></div>
                    <div class="b center mid fit-one-line" style="grid-column: 3 / {{ $t['titleEnd'] }}; grid-row: 1 / 3; font-size: {{ $t['size'] + 1.5 }}pt;">{{ $t['title'] }}</div>

                    <div class="b fit-one-line" style="grid-column: 3 / 6; grid-row: 3 / 4;">{{ $t['name'] }}</div>
                    <div class="b product-name fit-one-line" style="grid-column: 6 / 14; grid-row: 3 / 4; font-size: {{ $productFontSize }}pt;">{{ $productName }}</div>
                    <div class="lot-info-stack" style="grid-column: 14 / 21; grid-row: 4 / 11;">
                        <div class="lot-info-row"><span class="b">Số lô:</span><span class="batch-value">{{ $label['batch'] }}</span></div>
                        <div class="lot-info-row"><span class="b">NSX:</span><span class="fit-one-line">{{ $label['mfg'] }}</span></div>
                        <div class="lot-info-row"><span class="b">HSD:</span><span class="fit-one-line">{{ $label['exp'] }}</span></div>
                        <div class="lot-info-row" style="grid-template-columns: 9fr 3fr;"><span class="b fit-one-line">Khối lượng tịnh:</span><span class="b fit-one-line">{{ $label['weight'] }}</span></div>
                    </div>

                    <div class="b fit-one-line" style="grid-column: 3 / 6; grid-row: 5 / 6;">Tên khoa học:</div>
                    <div class="i scientific-name fit-wrap" style="grid-column: 6 / 14; grid-row: 5 / 7;">{{ $label['scientific'] }}</div>

                    <div class="b field-label-nowrap fit-one-line" style="grid-column: 3 / 6; grid-row: 7 / 8;">Bộ phận dùng:</div>
                    <div class="fit-wrap" style="grid-column: 6 / 14; grid-row: 7 / 9;">{{ $label['part'] }}</div>

                    <div class="b fit-one-line" style="grid-column: 3 / 6; grid-row: 9 / 10;">Nguồn gốc:</div>
                    <div class="fit-wrap" style="grid-column: 6 / 14; grid-row: 9 / 11;">{{ $label['origin'] }}</div>

                    <div class="b fit-one-line" style="grid-column: 3 / {{ $registrationValueStart }}; grid-row: 11 / 12;">{{ $t['registration'] }}</div>
                    <div class="fit-one-line" style="grid-column: {{ $registrationValueStart }} / 14; grid-row: 11 / 12;">{{ $label['registration'] }}</div>
                    @if(!empty($label['include_packaging_control']))
                        <div class="packaging-control-block" style="grid-column: 1 / 14; grid-row: 12 / 14; font-size: {{ $t['size'] - .4 }}pt;">
                            <div class="packaging-control-line fit-one-line">
                                <span>Kiểm soát đóng gói:</span>
                                <span class="b">IPC: {{ $label['packaging_controller'] }}</span>
                                <span class="i">Người thực hiện: {{ $label['packaging_performer'] }}</span>
                            </div>
                        </div>
                    @endif
                    <div class="quality-block fit-wrap" style="grid-column: 14 / 21; grid-row: 11 / 14;">
                        <div class="b">Tiêu chuẩn chất lượng:</div>
                        <div>{{ $label['standard'] }}</div>
                    </div>

                    <div class="b center mid" style="grid-column: 1 / 14; grid-row: 14 / 15;">Sản xuất tại:</div>
                    <div class="b center mid qr-heading" style="grid-column: 14 / 21; grid-row: 14 / 15;">QUÉT QR TRUY XUẤT</div>
                    <div class="b center mid qr-heading" style="grid-column: 14 / 21; grid-row: 15 / 16;">HỒ SƠ</div>
                    @if(!empty($label['qr_url']))
                        <div class="qr-box"><canvas data-qr-value="{{ $label['qr_url'] }}" aria-label="Mã QR truy xuất {{ $label['batch'] }}"></canvas></div>
                    @endif
                    <div class="b center mid fit-one-line" style="grid-column: 1 / 14; grid-row: 15 / 16;">CÔNG TY CỔ PHẦN DƯỢC LIỆU NINH HIỆP</div>
                    <div class="mid fit-one-line" style="grid-column: 1 / 14; grid-row: 16 / 17; justify-content: flex-start;">Địa chỉ: Số 34-35 Lô E Baza Long Vĩ, P.Từ Sơn, Bắc Ninh</div>
                    <div class="mid fit-one-line" style="grid-column: 1 / 14; grid-row: 17 / 18; justify-content: flex-start;">Nhà máy: Lô E4 CCN Đa Nghề Đông Thọ - Văn Môn - Bắc Ninh</div>
                    <div class="mid fit-one-line" style="grid-column: 1 / 14; grid-row: 18 / 19; justify-content: flex-start;">Điện thoại: 02223.883.356</div>
                    <div class="mid fit-one-line" style="grid-column: 1 / 14; grid-row: 19 / 20; justify-content: flex-start; font-size: {{ $t['size'] - .4 }}pt;">Email: duoclieuninhhiep@gmail.com - Website: duoclieuninhhiep.vn</div>

                    <div class="b center mid" style="grid-column: 1 / 3; grid-row: 20 / 22;">Bảo quản:</div>
                    <div class="mid fit-wrap" style="grid-column: 3 / 21; grid-row: 20 / 22; justify-content: flex-start; text-align: left;">{{ $label['storage'] ?? '' }}</div>
                </div>
            </div>
        @endforeach
    </section>
@endforeach
<script>
    (() => {
        const fitLabelText = () => {
            document.querySelectorAll('.product-name, .batch-value, .fit-one-line, .fit-wrap').forEach((element) => {
                const baseSize = parseFloat(element.dataset.baseFontSize || getComputedStyle(element).fontSize);
                element.dataset.baseFontSize = String(baseSize);
                const isWrapped = element.classList.contains('fit-wrap');
                const overflows = () => isWrapped
                    ? element.scrollHeight > element.clientHeight + 1
                    : element.scrollWidth > element.clientWidth + 1;

                let size = baseSize;
                element.style.fontSize = `${size}px`;
                const minimumSize = element.classList.contains('product-name') ? 0.1 : 0.5;
                while (overflows() && size > minimumSize) {
                    size = Math.max(minimumSize, size - 0.25);
                    element.style.fontSize = `${size}px`;
                }

                // Keep unusually long values visible too; only relax the one-line
                // rule when they cannot fit even at the smallest print size.
                const keepOneLine = element.classList.contains('packaging-control-line');
                if (!isWrapped && !element.classList.contains('product-name') && !keepOneLine && overflows()) {
                    element.style.whiteSpace = 'normal';
                    element.style.overflowWrap = 'anywhere';
                    while (element.scrollHeight > element.clientHeight + 1 && size > 0.1) {
                        size = Math.max(0.1, size - 0.1);
                        element.style.fontSize = `${size}px`;
                    }
                }
            });
        };

        window.fitLabelText = fitLabelText;
        window.addEventListener('beforeprint', fitLabelText);
        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(fitLabelText);
            document.querySelectorAll('.product-name, .batch-value, .fit-one-line, .fit-wrap').forEach((element) => observer.observe(element));
        }
        document.fonts?.ready.then(fitLabelText);
        requestAnimationFrame(fitLabelText);
    })();
</script>
</body>
</html>
