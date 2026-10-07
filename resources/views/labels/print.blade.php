@php
    $templates = [
        'vt' => [
            'title' => 'NGUYÊN LIỆU LÀM THUỐC - VỊ THUỐC CỔ TRUYỀN',
            'name' => 'Tên vị thuốc:',
            'registration' => 'SĐK/SCB:',
            'font' => '"Times New Roman", Times, serif',
            'size' => 9,
            'titleEnd' => 17,
            'registrationEnd' => 6,
            'rows' => [13.05, 13.95, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 13.05, 7.95, 13.05, 13.95, 13.95, 13.95, 13.95, 13.95, 12, 12],
        ],
        'dl_n' => [
            'title' => 'NGUYÊN LIỆU LÀM THUỐC - DƯỢC LIỆU TRONG NƯỚC',
            'name' => 'Tên dược liệu:',
            'registration' => 'SĐK/SCB:',
            'font' => 'Arial, Helvetica, sans-serif',
            'size' => 8.2,
            'titleEnd' => 18,
            'registrationEnd' => 6,
            'rows' => [14.4, 9.3, 12, 12, 12, 12, 12, 12, 12, 12, 14.4, 11.4, 11.4, 14.4, 15, 13.95, 13.95, 13.95, 13.95, 13.95, 13.95],
        ],
        'dl_b' => [
            'title' => 'NGUYÊN LIỆU LÀM THUỐC - DƯỢC LIỆU NHẬP KHẨU',
            'name' => 'Tên dược liệu:',
            'registration' => 'SĐK/SCB/Số GPNK:',
            'font' => 'Arial, Helvetica, sans-serif',
            'size' => 8.2,
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
        @page { size: A4 landscape; margin: 5mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page { width: 287mm; height: 199mm; display: grid; grid-template-columns: 141.5mm 141.5mm; grid-template-rows: 97.5mm 97.5mm; column-gap: 4mm; row-gap: 4mm; align-content: start; overflow: hidden; break-after: page; page-break-after: always; }
        .page:last-child { break-after: auto; page-break-after: auto; }
        .slot { width: 141.5mm; height: 97.5mm; }
        .label { position: relative; width: 141.5mm; height: 97.5mm; display: grid; border: 1mm double #000; line-height: 1.12; overflow: hidden; }
        .label > div { min-width: 0; min-height: 0; overflow: hidden; padding: .25mm .7mm; }
        .box-top { border-top: .25mm solid #000; }
        .box-v { border-left: .25mm solid #000; }
        .b { font-weight: bold; }
        .i { font-style: italic; }
        .center { text-align: center; }
        .mid { display: flex; align-items: center; justify-content: center; }
        .logo { display: flex; align-items: center; justify-content: center; padding: .6mm !important; }
        .logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .qr-box { grid-column: 14 / 21; grid-row: 16 / 20; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 0 !important; }
        .qr-box canvas { display: block; width: 18mm !important; height: 18mm !important; max-width: 100%; max-height: 100%; }
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
            @endphp
            <div class="slot">
                <div class="label" style="grid-template-columns: {{ $gridColumns }}; grid-template-rows: {{ $gridRows }}; font-family: {{ $t['font'] }}; font-size: {{ $t['size'] }}pt;">
                    {{-- Đường kẻ và khung --}}
                    <div class="box-top box-v" style="grid-column: 14 / 21; grid-row: 4 / 11; padding: 0;"></div>
                    <div class="box-top box-v" style="grid-column: 14 / 21; grid-row: 11 / 14; padding: 0;"></div>
                    <div class="box-top box-v" style="grid-column: 14 / 21; grid-row: 14 / 16; padding: 0;"></div>
                    <div class="box-top box-v" style="grid-column: 14 / 21; grid-row: 16 / 20; padding: 0;"></div>
                    <div style="grid-column: 1 / 14; grid-row: 13 / 14; padding: 0; position: relative;"><span style="position: absolute; left: 2mm; right: 2mm; bottom: 0; border-bottom: .25mm solid #000;"></span></div>
                    <div class="box-top" style="grid-column: 1 / 21; grid-row: 20 / 22; padding: 0;"></div>

                    <div class="logo" style="grid-column: 1 / 3; grid-row: 1 / 6;"><img src="{{ asset('images/label-logo.png') }}" alt="Logo"></div>
                    <div class="b center mid" style="grid-column: 3 / {{ $t['titleEnd'] }}; grid-row: 1 / 3; font-size: {{ $t['size'] + 1.5 }}pt;">{{ $t['title'] }}</div>

                    <div class="b" style="grid-column: 3 / 6; grid-row: 3 / 4;">{{ $t['name'] }}</div>
                    <div class="b" style="grid-column: 6 / 14; grid-row: 3 / 5;">{{ mb_strtoupper($label['product'], 'UTF-8') }}</div>
                    <div class="b" style="grid-column: 14 / 16; grid-row: 4 / 5; padding-top: 1.2mm;">Số lô:</div>
                    <div style="grid-column: 16 / 21; grid-row: 4 / 6; padding-top: 1.2mm; word-break: break-all;">{{ $label['batch'] }}</div>

                    <div class="b" style="grid-column: 3 / 6; grid-row: 5 / 6;">Tên khoa học:</div>
                    <div class="i" style="grid-column: 6 / 14; grid-row: 5 / 7;">{{ $label['scientific'] }}</div>
                    <div class="b" style="grid-column: 14 / 16; grid-row: 6 / 7;">NSX:</div>
                    <div style="grid-column: 16 / 21; grid-row: 6 / 7;">{{ $label['mfg'] }}</div>

                    <div class="b" style="grid-column: 3 / 6; grid-row: 7 / 8;">Bộ phận dùng:</div>
                    <div style="grid-column: 6 / 14; grid-row: 7 / 8;">{{ $label['part'] }}</div>
                    <div class="b" style="grid-column: 14 / 16; grid-row: 8 / 9;">HSD:</div>
                    <div style="grid-column: 16 / 21; grid-row: 8 / 9;">{{ $label['exp'] }}</div>

                    <div class="b" style="grid-column: 3 / 6; grid-row: 9 / 10;">Nguồn gốc:</div>
                    <div style="grid-column: 6 / 14; grid-row: 9 / 10;">{{ $label['origin'] }}</div>
                    <div class="b" style="grid-column: 14 / 19; grid-row: 10 / 11;">Khối lượng tịnh:</div>
                    <div class="b" style="grid-column: 19 / 21; grid-row: 10 / 11;">{{ $label['weight'] }}</div>

                    <div class="b" style="grid-column: 3 / {{ $registrationValueStart }}; grid-row: 11 / 12;">{{ $t['registration'] }}</div>
                    <div style="grid-column: {{ $registrationValueStart }} / 14; grid-row: 11 / 12;">{{ $label['registration'] }}</div>
                    <div class="b" style="grid-column: 14 / 21; grid-row: 11 / 12; padding-top: 1.2mm;">Tiêu chuẩn chất lượng:</div>
                    <div style="grid-column: 14 / 21; grid-row: 12 / 13;">{{ $label['standard'] }}</div>

                    <div class="b center mid" style="grid-column: 1 / 14; grid-row: 14 / 15;">Sản xuất tại:</div>
                    <div class="b center mid" style="grid-column: 14 / 21; grid-row: 14 / 15;">QUÉT QR TRUY XUẤT</div>
                    <div class="b center mid" style="grid-column: 14 / 21; grid-row: 15 / 16;">HỒ SƠ</div>
                    @if(!empty($label['qr_url']))
                        <div class="qr-box"><canvas data-qr-value="{{ $label['qr_url'] }}" aria-label="Mã QR truy xuất {{ $label['batch'] }}"></canvas></div>
                    @endif
                    <div class="b center mid" style="grid-column: 1 / 14; grid-row: 15 / 16;">CÔNG TY CỔ PHẦN DƯỢC LIỆU NINH HIỆP</div>
                    <div class="mid" style="grid-column: 1 / 14; grid-row: 16 / 17; justify-content: flex-start;">Địa chỉ: Số 34-35 Lô E Baza Long Vĩ, P.Từ Sơn, Bắc Ninh</div>
                    <div class="mid" style="grid-column: 1 / 14; grid-row: 17 / 18; justify-content: flex-start;">Nhà máy: Lô E4 CCN Đa Nghề Đông Thọ - Văn Môn - Bắc Ninh</div>
                    <div class="mid" style="grid-column: 1 / 14; grid-row: 18 / 19; justify-content: flex-start;">Điện thoại: 02223.883.356</div>
                    <div class="mid" style="grid-column: 1 / 14; grid-row: 19 / 20; justify-content: flex-start; font-size: {{ $t['size'] - .4 }}pt;">Email: duoclieuninhhiep@gmail.com - Website: duoclieuninhhiep.vn</div>

                    <div class="b center mid" style="grid-column: 1 / 3; grid-row: 20 / 22;">Bảo quản:</div>
                    <div class="mid" style="grid-column: 3 / 21; grid-row: 20 / 22; justify-content: flex-start; text-align: left;">{{ $label['storage'] ?? '' }}</div>
                </div>
            </div>
        @endforeach
    </section>
@endforeach
</body>
</html>
