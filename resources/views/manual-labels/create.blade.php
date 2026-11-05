<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Sản xuất / In Nhãn Tay</h2>
                <p class="mt-1 text-xs text-slate-500">Nhập thông tin nhãn, chọn số lượng và in. Mỗi lần in được lưu vào lịch sử.</p>
            </div>
            <a href="{{ route('manual-labels.history') }}" class="bg-slate-800 px-3 py-1.5 text-[10px] font-bold uppercase text-white hover:bg-slate-700">Lịch sử in nhãn tay</a>
        </div>
    </x-slot>

    @php
        $input = 'mt-1 w-full border border-slate-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-0';
        $lbl = 'block text-[10px] font-bold uppercase text-slate-600';
        $err = fn ($f) => $errors->first($f) ? '<p class="mt-0.5 text-[11px] text-rose-600">' . e($errors->first($f)) . '</p>' : '';
    @endphp

    <div class="py-3">
        <div class="max-w-4xl px-2 space-y-3">
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">Vui lòng kiểm tra lại các trường được đánh dấu lỗi.</div>@endif

            <form method="POST" action="{{ route('manual-labels.store') }}" target="_blank" class="border border-slate-300 bg-white p-4 space-y-4" x-data="{ type: '{{ old('label_type', $prefill['label_type'] ?? 'dl_n') }}', ref: @js(trim(($prefill['label_code'] ?? '') . (isset($prefill['order_code']) ? ' · Lệnh SX ' . $prefill['order_code'] : ''))), q: '', results: [], timer: null, search() { clearTimeout(this.timer); if (this.q.trim().length < 2) { this.results = []; return; } this.timer = setTimeout(async () => { const r = await fetch(@js(route('manual-labels.sources.search')) + '?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } }); this.results = r.ok ? await r.json() : []; }, 250); }, fill(t) { this.type = t.label_type; this.ref = (t.label_code ?? '') + (t.order_code ? ' · Lệnh SX ' + t.order_code : ''); for (const k of ['product_name','scientific_name','part_used','batch','mfg_date','exp_date','origin','registration','standard','net_weight','copies_count','order_code','label_code']) { const el = $root.elements[k]; if (el) { el.value = t[k] ?? ''; el.dispatchEvent(new Event('input')); } } const tm = $root.elements['traditional_medicine_name']; if (tm) tm.checked = !!t.traditional_medicine_name; this.results = []; this.q = ''; } }">
                @csrf
                <input type="hidden" name="order_code" value="{{ old('order_code', $prefill['order_code'] ?? null) }}">
                <input type="hidden" name="label_code" value="{{ old('label_code', $prefill['label_code'] ?? null) }}">
                <div x-show="ref" x-cloak class="flex items-center gap-2 border border-blue-100 bg-blue-50/60 px-3 py-1.5 text-xs text-slate-700">Mã nhãn: <b x-text="ref"></b></div>
                @if($hasSources)
                    <div class="relative rounded-md border border-blue-100 bg-blue-50/60 p-3">
                        <label class="{{ $lbl }}">Tìm lệnh sản xuất (mã nhãn, lệnh SX, tên hàng, số lô)</label>
                        <input type="text" x-model="q" @input="search()" autocomplete="off" placeholder="Gõ ít nhất 2 ký tự, VD: NH001 hoặc trần bì" class="{{ $input }} bg-white">
                        <ul x-show="results.length" x-cloak class="absolute left-3 right-3 z-10 mt-1 max-h-60 overflow-auto border border-slate-300 bg-white text-xs shadow">
                            <template x-for="r in results" :key="r.id">
                                <li><button type="button" @click="fill(r)" class="block w-full px-2 py-1 text-left hover:bg-blue-50"><span class="font-mono text-slate-500" x-text="r.label_code"></span> <span class="font-semibold" x-text="r.product_name"></span> <span class="font-mono text-slate-500" x-text="r.batch"></span></button></li>
                            </template>
                        </ul>
                    </div>
                @endif
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="{{ $lbl }}">Loại nhãn *</label>
                        <select name="label_type" x-model="type" class="{{ $input }}">
                            @foreach($labelTypes as $value => $text)
                                <option value="{{ $value }}">{{ $text }}</option>
                            @endforeach
                        </select>
                        {!! $err('label_type') !!}
                    </div>
                    <div class="flex items-end gap-4 pb-1.5 text-xs text-slate-700">
                        <label x-show="type === 'vt'" class="inline-flex items-center gap-1.5"><input type="checkbox" name="traditional_medicine_name" value="1" @checked(old('traditional_medicine_name', $prefill['traditional_medicine_name'] ?? null)) class="rounded border-slate-300"> Tên vị thuốc cổ truyền</label>
                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="watermark" value="1" @checked(old('watermark', $prefill['watermark'] ?? null)) class="rounded border-slate-300"> Watermark</label>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="{{ $lbl }}">Tên sản phẩm *</label>
                        <input data-check name="product_name" value="{{ old('product_name', $prefill['product_name'] ?? null) }}" class="{{ $input }}" required>
                        {!! $err('product_name') !!}
                    </div>
                    <div><label class="{{ $lbl }}">Tên khoa học</label><input data-check name="scientific_name" value="{{ old('scientific_name', $prefill['scientific_name'] ?? null) }}" class="{{ $input }}">{!! $err('scientific_name') !!}</div>
                    <div><label class="{{ $lbl }}">Bộ phận dùng</label><input data-check name="part_used" value="{{ old('part_used', $prefill['part_used'] ?? null) }}" class="{{ $input }}">{!! $err('part_used') !!}</div>
                    <div><label class="{{ $lbl }}">Số lô *</label><input data-check name="batch" value="{{ old('batch', $prefill['batch'] ?? null) }}" class="{{ $input }}" required>{!! $err('batch') !!}</div>
                    <div><label class="{{ $lbl }}">Khối lượng tịnh</label><input data-check name="net_weight" value="{{ old('net_weight', $prefill['net_weight'] ?? null) }}" placeholder="VD: 10 kg" class="{{ $input }}">{!! $err('net_weight') !!}</div>
                    <div><label class="{{ $lbl }}">NSX</label><input data-check name="mfg_date" value="{{ old('mfg_date', $prefill['mfg_date'] ?? null) }}" placeholder="dd/mm/yyyy" class="{{ $input }}">{!! $err('mfg_date') !!}</div>
                    <div><label class="{{ $lbl }}">HSD</label><input data-check name="exp_date" value="{{ old('exp_date', $prefill['exp_date'] ?? null) }}" placeholder="dd/mm/yyyy" class="{{ $input }}">{!! $err('exp_date') !!}</div>
                    <div><label class="{{ $lbl }}">Nguồn gốc</label><input data-check name="origin" value="{{ old('origin', $prefill['origin'] ?? null) }}" class="{{ $input }}">{!! $err('origin') !!}</div>
                    <div class="sm:col-span-2"><label class="{{ $lbl }}" x-text="type === 'dl_b' ? 'Số đăng ký (SĐK) / Số công bố (SCB) / Số giấy phép nhập khẩu (GPNK)' : 'Số đăng ký (SĐK) / Số công bố (SCB)'">Số đăng ký (SĐK) / Số công bố (SCB)</label><input data-check name="registration" value="{{ old('registration', $prefill['registration'] ?? null) }}" placeholder="VD: VD-12345-20 hoặc 123/2020/ĐKSP" class="{{ $input }}">{!! $err('registration') !!}</div>
                    <div><label class="{{ $lbl }}">Tiêu chuẩn chất lượng</label><input data-check name="standard" value="{{ old('standard', $prefill['standard'] ?? null) }}" class="{{ $input }}">{!! $err('standard') !!}</div>
                    <div><label class="{{ $lbl }}">Bảo quản</label><input data-check name="storage" value="{{ old('storage', $prefill['storage'] ?? null) }}" class="{{ $input }}">{!! $err('storage') !!}</div>
                                        <div class="sm:col-span-2"><label class="{{ $lbl }}">Nội dung mã QR</label><input name="qr_content" value="{{ old('qr_content', $prefill['qr_content'] ?? null) }}" placeholder="Link hoặc nội dung; để trống nếu không in mã QR" class="{{ $input }}">{!! $err('qr_content') !!}</div>                </div>

                <div class="grid gap-3 border-t border-slate-200 pt-3 sm:grid-cols-3">
                    <div><label class="{{ $lbl }}">Số lượng nhãn in *</label><input type="number" min="1" max="1000" name="copies_count" value="{{ old('copies_count', $prefill['copies_count'] ?? 1) }}" class="{{ $input }}" required>{!! $err('copies_count') !!}</div>
                    <div class="sm:col-span-2"><label class="{{ $lbl }}">Ghi chú</label><input name="note" value="{{ old('note', $prefill['note'] ?? null) }}" class="{{ $input }}">{!! $err('note') !!}</div>
                </div>

                <div class="flex justify-end">
                    <span id="check-summary" class="mr-3 self-center text-xs font-semibold text-rose-600"></span>
                    <button type="submit" class="bg-blue-700 px-5 py-2 text-xs font-bold uppercase text-white hover:bg-blue-600">In nhãn</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fields = [...document.querySelectorAll('input[data-check]')];
            const summary = document.getElementById('check-summary');
            const bad = (v) => v.trim() === '' || /^#?n\/?a$/i.test(v.trim()) || /^#(n\/a|ref!|value!|name\?)$/i.test(v.trim());
            const refresh = () => {
                let count = 0;
                fields.forEach((el) => {
                    const wrong = bad(el.value);
                    if (wrong) count++;
                    el.classList.toggle('border-rose-500', wrong);
                    el.classList.toggle('bg-rose-50', wrong);
                    el.classList.toggle('border-slate-300', !wrong);
                    let note = el.parentElement.querySelector('.check-note');
                    if (wrong && !note) {
                        note = document.createElement('p');
                        note.className = 'check-note mt-0.5 text-[11px] text-rose-600';
                        el.after(note);
                    }
                    if (note) {
                        if (wrong) note.textContent = el.value.trim() === '' ? 'Đang để trống' : 'Giá trị không hợp lệ (NA)';
                        else note.remove();
                    }
                });
                summary.textContent = count ? count + ' ô thiếu hoặc NA (vẫn có thể in)' : '';
            };
            fields.forEach((el) => el.addEventListener('input', refresh));
            refresh();
        });
    </script></x-app-layout>
