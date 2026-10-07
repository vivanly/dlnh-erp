@props(['default' => 100, 'options' => [100, 500, 1000, 5000, 10000, 50000]])

<form method="GET" action="{{ url()->current() }}" class="inline-flex items-center gap-2 whitespace-nowrap text-xs text-slate-600">
    @foreach(request()->query() as $key => $value)
        @if(!in_array($key, ['per_page', 'page'], true) && is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <label for="per-page-select" class="whitespace-nowrap">Hiển thị</label>
    <select id="per-page-select" name="per_page" aria-label="Số dòng hiển thị" class="text-xs border-slate-300 rounded-none py-1.5" onchange="this.form.submit()">
        @foreach($options as $size)
            <option value="{{ $size }}" {{ (int) request('per_page', $default) === $size ? 'selected' : '' }}>{{ $size }} dòng</option>
        @endforeach
    </select>
</form>