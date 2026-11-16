<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            foreach ([
                'Dược Liệu' => 'DL',
                'Dược Liệu Sơ Chế' => 'DLSC',
                'DS' => 'DLSC',
                'Vị Thuốc Cổ Truyền' => 'VTCT',
                'VT' => 'VTCT',
                'Thuốc Cổ Truyền' => 'TCT',
                'Thực Phẩm Chức Năng' => 'TPCN',
            ] as $old => $code) {
                DB::table('products')->where('classification', $old)->update(['classification' => $code]);
            }

            foreach ([
                'raw_material' => 'DLT',
                'ingredient' => 'NL',
                'accessory' => 'PL',
                'excipient' => 'TD',
                'packaging' => 'BB',
            ] as $old => $code) {
                DB::table('purchase_order_items')
                    ->where('material_type', $old)
                    ->update(['material_type' => $code]);
            }

            foreach ([
                'NL' => 'DLT',
                'DS' => 'DLSC',
                'VT' => 'VTCT',
            ] as $old => $code) {
                $orders = DB::table('orders')->where('order_type', $old)->get(['id', 'order_code']);
                foreach ($orders as $order) {
                    $newOrderCode = str_starts_with($order->order_code, $old)
                        ? $code.substr($order->order_code, strlen($old))
                        : $order->order_code;

                    if ($newOrderCode !== $order->order_code && DB::table('orders')
                        ->where('order_code', $newOrderCode)
                        ->where('id', '<>', $order->id)
                        ->exists()) {
                        throw new RuntimeException("Cannot migrate order code {$order->order_code}: {$newOrderCode} already exists.");
                    }

                    if ($newOrderCode !== $order->order_code) {
                        foreach (DB::table('manual_label_prints')->where('order_code', $order->order_code)->get(['id', 'qr_content']) as $print) {
                            DB::table('manual_label_prints')->where('id', $print->id)->update([
                                'order_code' => $newOrderCode,
                                'qr_content' => $print->qr_content === null
                                    ? null
                                    : str_replace($order->order_code, $newOrderCode, $print->qr_content),
                            ]);
                        }
                    }

                    DB::table('orders')->where('id', $order->id)->update([
                        'order_type' => $code,
                        'order_code' => $newOrderCode,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        if (DB::table('orders')->whereIn('order_type', ['VT', 'TCT', 'TPCN'])->exists()
            || DB::table('products')->where('classification', 'VT')->exists()) {
            throw new RuntimeException('Cannot reverse type codes while orders or products use the new VT, TCT, or TPCN types.');
        }

        DB::transaction(function () {
            foreach ([
                'DLT' => 'raw_material',
                'NL' => 'ingredient',
                'PL' => 'accessory',
                'TD' => 'excipient',
                'BB' => 'packaging',
            ] as $code => $old) {
                DB::table('purchase_order_items')
                    ->where('material_type', $code)
                    ->update(['material_type' => $old]);
            }

            foreach ([
                'DL' => 'Dược Liệu',
                'DLSC' => 'Dược Liệu Sơ Chế',
                'VTCT' => 'Vị Thuốc Cổ Truyền',
                'TCT' => 'Thuốc Cổ Truyền',
                'TPCN' => 'Thực Phẩm Chức Năng',
            ] as $code => $label) {
                DB::table('products')->where('classification', $code)->update(['classification' => $label]);
            }

            foreach ([
                'DLT' => 'NL',
                'DLSC' => 'DS',
                'VTCT' => 'VT',
            ] as $code => $old) {
                $orders = DB::table('orders')->where('order_type', $code)->get(['id', 'order_code']);
                foreach ($orders as $order) {
                    $newOrderCode = str_starts_with($order->order_code, $code)
                        ? $old.substr($order->order_code, strlen($code))
                        : $order->order_code;

                    if ($newOrderCode !== $order->order_code && DB::table('orders')
                        ->where('order_code', $newOrderCode)
                        ->where('id', '<>', $order->id)
                        ->exists()) {
                        throw new RuntimeException("Cannot reverse order code {$order->order_code}: {$newOrderCode} already exists.");
                    }

                    if ($newOrderCode !== $order->order_code) {
                        foreach (DB::table('manual_label_prints')->where('order_code', $order->order_code)->get(['id', 'qr_content']) as $print) {
                            DB::table('manual_label_prints')->where('id', $print->id)->update([
                                'order_code' => $newOrderCode,
                                'qr_content' => $print->qr_content === null
                                    ? null
                                    : str_replace($order->order_code, $newOrderCode, $print->qr_content),
                            ]);
                        }
                    }

                    DB::table('orders')->where('id', $order->id)->update([
                        'order_type' => $old,
                        'order_code' => $newOrderCode,
                    ]);
                }
            }
        });
    }
};
