<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('manual_label_sequences')->insertOrIgnore([
            'id' => 1,
            'last_number' => 0,
        ]);
    }

    public function down(): void
    {
        DB::table('manual_label_sequences')->where('id', 1)->delete();
    }
};
