<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $codes = DB::table('manual_label_production_orders')->pluck('label_code')
            ->merge(DB::table('manual_label_prints')->whereNotNull('label_code')->pluck('label_code'))
            ->unique()->values();
        $mapping = [];
        $seen = [];
        $max = 0;
        foreach ($codes as $code) {
            if (! preg_match('/^NH(\d{1,6})$/i', $code, $matches) || (int) $matches[1] < 1) {
                throw new RuntimeException("Không thể chuyển mã nhãn không hợp lệ: {$code}");
            }
            $number = (int) $matches[1];
            $normalized = sprintf('NH%06d', $number);
            if (isset($seen[$normalized]) && $seen[$normalized] !== $code) {
                throw new RuntimeException("Hai mã nhãn {$seen[$normalized]} và {$code} sẽ bị trùng khi chuyển đổi.");
            }
            $seen[$normalized] = $code;
            $mapping[$code] = $normalized;
            $max = max($max, $number);
        }

        DB::transaction(function () use ($mapping) {
            foreach ($mapping as $old => $new) {
                if ($old !== $new) {
                    DB::table('manual_label_production_orders')->where('label_code', $old)->update(['label_code' => $new]);
                    DB::table('manual_label_prints')->where('label_code', $old)->update(['label_code' => $new]);
                }
            }
        });

        Schema::create('manual_label_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedInteger('last_number');
        });
        DB::table('manual_label_sequences')->insert(['id' => 1, 'last_number' => $max]);
    }

    public function down(): void
    {
        // Không thể tự động khôi phục định dạng mã nhãn cũ, nhưng cho phép rollback nếu cần thiết
        // DB::table('manual_label_sequences')->delete();
    }
};
