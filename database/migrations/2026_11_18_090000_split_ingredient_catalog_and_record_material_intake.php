<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('classification')->nullable();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('scientific_name')->nullable();
            $table->string('origin')->nullable();
            $table->string('part_used')->nullable();
            $table->string('unit')->nullable();
            $table->string('scientific_name_reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('material_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_code')->nullable()->unique();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('preliminary_qa_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('preliminary_qc_by')->constrained('users')->restrictOnDelete();
            $table->text('preliminary_note')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();
        });

        Schema::create('material_lot_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_lot_id')->constrained('material_lots')->cascadeOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('result', 24);
            $table->text('note')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });

        Schema::table('material_lots', function (Blueprint $table) {
            $table->foreignId('material_receipt_id')->nullable()->after('purchase_order_item_id')
                ->constrained('material_receipts')->nullOnDelete();
            $table->string('stock_receipt_code')->nullable()->unique();
            $table->foreignId('stock_received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('stock_received_at')->nullable();
        });

        Schema::table('material_stock_movements', function (Blueprint $table) {
            $table->foreignId('material_receipt_id')->nullable()->constrained('material_receipts')->nullOnDelete();
            $table->foreignId('material_lot_id')->nullable()->constrained('material_lots')->nullOnDelete();
        });

        Schema::table('production_material_lots', function (Blueprint $table) {
            $table->foreignId('material_lot_id')->nullable()->constrained('material_lots')->nullOnDelete();
        });

        Schema::table('supplier_return_orders', function (Blueprint $table) {
            $table->string('qc_test_report', 255)->nullable()->change();
            $table->date('qc_date')->nullable()->change();
        });

        $ingredientItems = DB::table('purchase_order_items')
            ->whereIn('material_type', ['NL', 'ingredient'])
            ->get(['id', 'material_id']);
        $ingredientIds = $ingredientItems->pluck('material_id')->filter()->unique()->values();
        $accessoryIds = DB::table('purchase_order_items')
            ->whereIn('material_type', ['PL', 'accessory'])
            ->pluck('material_id')
            ->filter()
            ->unique()
            ->values();

        if ($ingredientIds->isNotEmpty()) {
            $catalogColumns = [
                'id', 'classification', 'sku', 'name', 'slug', 'scientific_name', 'origin',
                'part_used', 'unit', 'scientific_name_reference', 'note', 'created_at', 'updated_at',
            ];
            foreach (DB::table('accessories')->whereIn('id', $ingredientIds)->orderBy('id')->get($catalogColumns) as $item) {
                DB::table('ingredients')->insert((array) $item);
            }
        }

        $purchaseItemIds = $ingredientItems->pluck('id')->values();
        foreach ($purchaseItemIds->chunk(500) as $ids) {
            DB::table('material_lots')
                ->whereIn('purchase_order_item_id', $ids)
                ->where('material_type', 'accessory')
                ->update(['material_type' => 'ingredient']);
            DB::table('material_stock_movements')
                ->whereIn('purchase_order_item_id', $ids)
                ->where('material_type', 'accessory')
                ->update(['material_type' => 'ingredient']);
            DB::table('supplier_return_orders')
                ->whereIn('purchase_order_item_id', $ids)
                ->where('material_type', 'accessory')
                ->update(['material_type' => 'ingredient']);
        }

        foreach ($ingredientIds->diff($accessoryIds)->chunk(500) as $ids) {
            DB::table('material_lots')->whereIn('material_id', $ids)->where('material_type', 'accessory')->update(['material_type' => 'ingredient']);
            DB::table('material_stock_movements')->whereIn('material_id', $ids)->where('material_type', 'accessory')->update(['material_type' => 'ingredient']);
            DB::table('supplier_return_orders')->whereIn('material_id', $ids)->where('material_type', 'accessory')->update(['material_type' => 'ingredient']);
        }

        DB::table('purchase_order_items')->where('material_type', 'ingredient')->update(['material_type' => 'NL']);

        foreach (DB::table('material_lots')->where('status', 'active')->where('material_type', '<>', 'raw_material')->orderBy('id')->get(['id', 'purchase_order_item_id', 'material_type', 'material_id', 'quantity']) as $lot) {
            $receiptMovement = DB::table('material_stock_movements')
                ->where('purchase_order_item_id', $lot->purchase_order_item_id)
                ->where('material_type', $lot->material_type)
                ->where('material_id', $lot->material_id)
                ->where('movement_type', 'RECEIVE_PURCHASE')
                ->where('direction', 'in')
                ->where('quantity', $lot->quantity)
                ->whereNull('material_lot_id')
                ->orderBy('id')
                ->first(['id']);

            if ($receiptMovement) {
                DB::table('material_stock_movements')
                    ->where('id', $receiptMovement->id)
                    ->update(['material_lot_id' => $lot->id]);
            }
        }

        foreach (DB::table('material_lots')->where('status', 'rejected')->whereNotNull('qc_note')->orderBy('id')->get(['id', 'purchase_order_item_id', 'quantity', 'qc_note']) as $lot) {
            $rejectionMovement = DB::table('material_stock_movements')
                ->where('purchase_order_item_id', $lot->purchase_order_item_id)
                ->where('movement_type', 'REJECT_PURCHASE')
                ->where('quantity', $lot->quantity)
                ->where('note', $lot->qc_note)
                ->orderBy('id')
                ->first(['id']);

            if ($rejectionMovement) {
                DB::table('material_stock_movements')
                    ->where('id', $rejectionMovement->id)
                    ->update(['movement_type' => 'QC_REJECT']);
            }
        }
    }

    public function down(): void
    {
        $skuConflict = DB::table('ingredients as ingredients')
            ->join('accessories as accessories', 'accessories.sku', '=', 'ingredients.sku')
            ->whereColumn('accessories.id', '<>', 'ingredients.id')
            ->value('ingredients.sku');
        if ($skuConflict !== null) {
            throw new RuntimeException("Cannot merge ingredient {$skuConflict} into accessories because its SKU already exists.");
        }

        $ingredientIds = [];
        foreach (DB::table('ingredients')->orderBy('id')->get() as $ingredient) {
            $sameId = DB::table('accessories')->where('id', $ingredient->id)->first(['id', 'sku']);
            if ($sameId && $sameId->sku === $ingredient->sku) {
                $data = (array) $ingredient;
                unset($data['id']);
                DB::table('accessories')->where('id', $ingredient->id)->update($data);
                $ingredientIds[$ingredient->id] = $ingredient->id;

                continue;
            }

            $data = (array) $ingredient;
            unset($data['id']);
            $ingredientIds[$ingredient->id] = DB::table('accessories')->insertGetId($data);
        }

        if ($ingredientIds !== []) {
            $materialIdCase = 'CASE material_id';
            $componentIdCase = 'CASE component_id';
            foreach ($ingredientIds as $ingredientId => $accessoryId) {
                $materialIdCase .= " WHEN {$ingredientId} THEN {$accessoryId}";
                $componentIdCase .= " WHEN {$ingredientId} THEN {$accessoryId}";
            }
            $materialIdCase .= ' ELSE material_id END';
            $componentIdCase .= ' ELSE component_id END';

            DB::table('purchase_order_items')
                ->where('material_type', 'NL')
                ->update(['material_id' => DB::raw($materialIdCase)]);

            foreach (['material_lots', 'material_stock_movements', 'supplier_return_orders'] as $table) {
                DB::table($table)
                    ->where('material_type', 'ingredient')
                    ->update(['material_type' => 'accessory', 'material_id' => DB::raw($materialIdCase)]);
            }

            DB::table('product_bom_items')
                ->where('component_type', 'App\\Models\\Ingredient')
                ->update([
                    'component_type' => 'App\\Models\\Accessory',
                    'component_id' => DB::raw($componentIdCase),
                ]);
        }

        Schema::table('supplier_return_orders', function (Blueprint $table) {
            $table->string('qc_test_report', 255)->nullable(false)->change();
            $table->date('qc_date')->nullable(false)->change();
        });

        Schema::table('production_material_lots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_lot_id');
        });

        Schema::table('material_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_receipt_id');
            $table->dropConstrainedForeignId('material_lot_id');
        });

        Schema::table('material_lots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_receipt_id');
            $table->dropUnique(['stock_receipt_code']);
            $table->dropConstrainedForeignId('stock_received_by');
            $table->dropColumn(['stock_receipt_code', 'stock_received_at']);
        });

        Schema::dropIfExists('material_lot_inspections');
        Schema::dropIfExists('material_receipts');
        Schema::dropIfExists('ingredients');
    }
};
