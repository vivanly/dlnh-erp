<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Quản lý PPCB
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Danh sách PPCB</h3>
                    <a href="{{ route('ppcb.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-xl shadow-xs transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        Thêm mới PPCB
                    </a>
                </div>

                @if (session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm font-medium">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3.5 bg-gray-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-16">STT</th>
                                <th class="px-6 py-3.5 bg-gray-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Mã</th>
                                <th class="px-6 py-3.5 bg-gray-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tên PPCB</th>
                                <th class="px-6 py-3.5 bg-gray-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Chi tiết</th>
                                <th class="px-6 py-3.5 bg-gray-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Ghi chú</th>
                                <th class="px-6 py-3.5 bg-gray-50 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($ppcbs as $index => $item)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <!-- Cột STT (Dùng vòng lặp $loop->iteration hoặc $index + 1) -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-500">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900">{{ $item->ma }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800">{{ $item->ten_ppcb }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $item->chi_tiet_ppcb }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $item->ghi_chu }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="inline-flex items-center gap-2">
                                            <!-- Nút Sửa -->
                                            <a href="{{ route('ppcb.edit', $item->id) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition">
                                                Sửa
                                            </a>
                                            <!-- Nút Xóa -->
                                            <form action="{{ route('ppcb.destroy', $item->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-semibold transition" onclick="return confirm('Bạn có chắc chắn muốn xóa bản ghi này không?')">
                                                    Xóa
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">Chưa có dữ liệu phương pháp chế biến nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>