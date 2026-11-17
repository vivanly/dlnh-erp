<?php

namespace Tests\Feature;

use App\Models\ManualLabelPrint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManualLabelPrintTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'label_type' => 'dl_n',
            'product_name' => 'Bạch truật',
            'batch' => 'LOT-01',
            'copies_count' => 3,
            'qr_content' => 'https://example.com/lot-01',
            'order_code' => 'VT26010001',
        ], $overrides);
    }

    public function test_unauthorized_user_cannot_access(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => false]);

        $this->actingAs($user)->get(route('manual-labels.create'))->assertForbidden();
        $this->actingAs($user)->post(route('manual-labels.store'), $this->payload())->assertForbidden();
        $this->actingAs($user)->get(route('manual-labels.history'))->assertForbidden();
    }

    public function test_manual_orders_prefill_print_form_and_search_sources_without_excel_import(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $blocked = User::factory()->create(['role' => 'production', 'can_print_labels' => false]);
        \App\Models\ManualLabelProduct::create([
            'name' => 'TRẦN BÌ SAO VÀNG',
            'name_key' => \App\Models\ManualLabelProduct::key('TRẦN BÌ SAO VÀNG'),
            'category' => 'Vị thuốc',
            'scientific_name' => 'Pericarpium Citri reticulatae perenne Praeparata',
            'standard' => 'TCCS',
            'origin' => 'Việt Nam',
            'part_used' => 'Vỏ quả',
            'registration' => 'VCT-00716-26',
        ]);
        \App\Models\ManualLabelProductionOrder::create([
            'label_code' => 'NH000001', 'production_order' => '26080001', 'order_date' => '2026-08-11',
            'name' => 'TRẦN BÌ SAO VÀNG', 'batch' => '110826TRBIN-SV', 'mfg_date' => '2026-08-11',
            'exp_date' => '2028-08-10', 'pack_spec' => '2 KG', 'quantity' => '160 KG',
            'label_count' => 80, 'label_type' => 'vt', 'traditional_medicine_name' => true,
        ]);
        \App\Models\ManualLabelProductionOrder::create([
            'label_code' => 'NH000002', 'production_order' => '26080002', 'name' => 'HÀNG LẠ',
            'batch' => '050126ABC', 'pack_spec' => '1 KG', 'quantity' => '10 KG', 'label_count' => 10,
        ]);

        $this->actingAs($user)->get('/manual-label-sources')->assertNotFound();
        $this->actingAs($user)->post('/manual-label-sources', [])->assertNotFound();
        $this->actingAs($user)->get(route('manual-labels.orders', ['q' => 'nh000001']))
            ->assertOk()->assertSee('TRẦN BÌ SAO VÀNG')->assertSee('Chưa in')->assertDontSee('Import Excel');
        $this->actingAs($user)->get(route('manual-labels.orders', ['q' => 'NH000002']))
            ->assertOk()->assertSee('chưa có mã hàng');
        $this->actingAs($blocked)->get(route('manual-labels.orders'))->assertForbidden();

        $order = \App\Models\ManualLabelProductionOrder::where('label_code', 'NH000001')->firstOrFail();
        $this->actingAs($user)->get(route('manual-labels.create', ['order' => $order->id]))->assertOk()
            ->assertSee('value="110826TRBIN-SV"', false)->assertSee('value="VCT-00716-26"', false)
            ->assertSee('value="2 KG"', false)->assertSee('value="80"', false)->assertSee('value="TCCS"', false)
            ->assertSee('value="11/08/2026"', false)->assertSee('value="10/08/2028"', false)->assertSee('value="NH000001"', false);
        $this->actingAs($blocked)->get(route('manual-labels.create', ['order' => $order->id]))->assertForbidden();

        $this->actingAs($user)->getJson(route('manual-labels.sources.search', ['q' => 'NH000001']))
            ->assertOk()->assertJsonPath('0.label_type', 'vt')->assertJsonPath('0.registration', 'VCT-00716-26')->assertJsonPath('0.origin', 'Việt Nam');

        $this->actingAs($user)->post(route('manual-labels.store'), $this->payload([
            'label_code' => 'NH000001', 'order_code' => '26080001', 'product_name' => 'TRẦN BÌ SAO VÀNG',
            'batch' => '110826TRBIN-SV', 'net_weight' => '2 kg', 'copies_count' => 30, 'label_type' => 'vt',
            'traditional_medicine_name' => '1',
        ]))->assertOk();
        $this->actingAs($user)->get(route('manual-labels.orders', ['q' => 'NH000001']))
            ->assertSee('30 / 80')->assertSee('In thiếu 50');
    }

    public function test_manual_order_form_places_label_fields_after_lot_dates_and_hides_excel_import(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $this->actingAs($user)->get(route('manual-labels.orders.create'))->assertOk()
            ->assertSeeInOrder(['Tên hàng *', 'Số lô *', 'NSX', 'HSD', 'QCĐG * (KG)', 'Số lượng * (KG)', 'Mẫu nhãn *'])
            ->assertDontSee('Import Excel');
    }
    public function test_manage_orders_and_products_crud(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $blocked = User::factory()->create(['role' => 'production', 'can_print_labels' => false]);

        $this->actingAs($blocked)->get(route('manual-labels.products'))->assertForbidden();
        $this->actingAs($blocked)->post(route('manual-labels.orders.store'), [])->assertForbidden();

        $this->actingAs($user)->get(route('manual-labels.products.create'))->assertOk();
        $this->actingAs($user)->post(route('manual-labels.products.store'), ['name' => 'Bạch  Thược', 'origin' => 'Việt Nam'])->assertRedirect(route('manual-labels.products'));
        $this->actingAs($user)->post(route('manual-labels.products.store'), ['name' => 'bạch thược'])->assertSessionHasErrors();
        $product = \App\Models\ManualLabelProduct::firstOrFail();
        $this->actingAs($user)->get(route('manual-labels.products.edit', $product))->assertOk()->assertSee('Việt Nam');
        $this->actingAs($user)->put(route('manual-labels.products.update', $product), ['name' => 'Bạch Thược', 'registration' => 'VD-1'])->assertRedirect();
        $this->assertDatabaseHas('manual_label_products', ['id' => $product->id, 'registration' => 'VD-1']);
        $this->actingAs($user)->get(route('manual-labels.products', ['q' => 'VD-1']))->assertOk()->assertSee('Bạch Thược');

        $this->actingAs($user)->get(route('manual-labels.orders.create'))->assertOk();
        $this->actingAs($user)->post(route('manual-labels.orders.store'), [
            'production_order' => 'SX01', 'lines' => [['name' => 'Bạch Thược', 'batch' => '010126X', 'pack_spec' => '1 kg', 'quantity' => '8 kg', 'mfg_date' => '2026-01-01', 'label_type' => 'vt']],
        ])->assertRedirect(route('manual-labels.orders'));
        $order = \App\Models\ManualLabelProductionOrder::firstOrFail();
        $this->actingAs($user)->get(route('manual-labels.orders.edit', $order))->assertOk()->assertSee('NH000001');
        $this->actingAs($user)->put(route('manual-labels.orders.update', $order), ['production_order' => 'SX01', 'name' => 'Bạch Thược', 'batch' => '010126X', 'pack_spec' => '1 kg', 'quantity' => '12 kg', 'label_type' => 'vt'])->assertRedirect(route('manual-labels.orders'));
        $this->assertDatabaseHas('manual_label_production_orders', ['id' => $order->id, 'label_count' => 12]);
        $this->actingAs($user)->delete(route('manual-labels.orders.destroy', $order))->assertRedirect();
        $this->actingAs($user)->delete(route('manual-labels.products.destroy', $product))->assertRedirect();
        $this->assertDatabaseCount('manual_label_production_orders', 0);
        $this->assertDatabaseCount('manual_label_products', 0);
    }

    public function test_authorized_user_prints_and_delivers_labels(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);

        $this->actingAs($user)->get(route('manual-labels.create'))->assertOk();
        $this->actingAs($user)->post(route('manual-labels.store'), $this->payload(['watermark' => '1']))
            ->assertOk()->assertSee('BẠCH TRUẬT')->assertSee('https://example.com/lot-01', false)->assertDontSee('<div class="watermark">', false);

        $print = ManualLabelPrint::firstOrFail();
        $this->assertSame(3, $print->copies_count);
        $this->assertFalse((bool) $print->watermark);

        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['quantity' => 4])->assertSessionHasErrors('quantity');
        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['quantity' => 2])->assertRedirect();
        $this->assertSame(1, $print->fresh()->remaining_count);
        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['quantity' => 1]);
        $this->assertSame(0, $print->fresh()->remaining_count);

        $this->actingAs($user)->get(route('manual-labels.history'))->assertOk()->assertSee('Quản lý nhãn')->assertSee('Thực xuất')->assertSee('VT26010001');
        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['kind' => 'return', 'quantity' => 5])->assertSessionHasErrors('quantity');
        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['kind' => 'return', 'quantity' => 1, 'customer' => 'Công ty A'])->assertRedirect();
        $print->refresh();
        $this->assertSame(1, $print->returned_count);
        $this->assertSame('Công ty A', $print->customer);
        $this->assertSame(1, $print->remaining_count);
        $this->actingAs($user)->get(route('manual-labels.history', ['q' => 'Công ty A', 'status' => 'stock']))->assertOk()->assertSee('VT26010001');
        $print->update(['watermark' => true]);
        $this->actingAs($user)->post(route('manual-labels.reprint', $print))->assertOk()->assertDontSee('<div class="watermark">', false);

        $this->actingAs($user)->delete(route('manual-labels.destroy', $print))->assertRedirect();
        $this->assertDatabaseMissing('manual_label_prints', ['id' => $print->id]);
        $this->assertDatabaseCount('manual_label_deliveries', 0);
    }

    public function test_batch_print_groups_lines_tracks_remaining_and_handles_partial_sheets(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        \App\Models\ManualLabelProduct::create(['name' => 'Bạch Thược', 'name_key' => \App\Models\ManualLabelProduct::key('Bạch Thược'), 'category' => 'Vị thuốc']);
        $line = ['name' => 'Bạch Thược', 'batch' => 'LOT1', 'mfg_date' => '2026-01-01', 'exp_date' => '2028-01-01'];
        $this->actingAs($user)->post(route('manual-labels.orders.store'), [
            'production_order' => 'SX01',
            'lines' => [
                $line + ['pack_spec' => '3 kg', 'quantity' => '10 kg', 'label_type' => 'vt', 'traditional_medicine_name' => '1'],
                $line + ['pack_spec' => '1 kg', 'quantity' => '3 kg', 'label_type' => 'dl_n', 'traditional_medicine_name' => '1'],
            ],
        ])->assertRedirect(route('manual-labels.orders'));

        $orders = \App\Models\ManualLabelProductionOrder::orderBy('id')->get();
        $this->assertSame(['NH000001', 'NH000002'], $orders->pluck('label_code')->all());
        $this->assertTrue($orders[0]->traditional_medicine_name);
        $this->assertFalse($orders[1]->traditional_medicine_name);
        $trackedQueries = false;
        $lookupQueries = 0;
        DB::listen(function ($query) use (&$trackedQueries, &$lookupQueries) {
            if ($trackedQueries
                && str_starts_with(strtolower($query->sql), 'select')
                && (str_contains($query->sql, 'manual_label_products') || str_contains($query->sql, 'manual_label_prints'))) {
                $lookupQueries++;
            }
        });
        $trackedQueries = true;
        $page = $this->actingAs($user)->get(route('manual-labels.batch.create', ['order' => 'SX01']));
        $trackedQueries = false;
        $this->assertSame(2, $lookupQueries);
        $page->assertOk()->assertSee('Còn 1000 g')->assertSee('NH000001')->assertSee('NH000002');

        $counts = [$orders[0]->id => 3, $orders[1]->id => 3];
        $lookupQueries = 0;
        $trackedQueries = true;
        $response = $this->actingAs($user)->post(route('manual-labels.batch.store'), [
            'production_order' => 'SX01', 'counts' => $counts, 'include_partial' => '0',
        ]);
        $trackedQueries = false;
        $this->assertSame(2, $lookupQueries);
        $response->assertOk()->assertSee('page column-first')->assertSee('Tên vị thuốc cổ truyền:')->assertSee('DƯỢC LIỆU TRONG NƯỚC');
        $this->assertSame(4, substr_count($response->getContent(), '<div class="slot">'));
        $this->assertDatabaseHas('manual_label_prints', ['label_code' => 'NH000001', 'copies_count' => 3]);
        $this->assertDatabaseHas('manual_label_prints', ['label_code' => 'NH000002', 'copies_count' => 1]);
        $page = $this->actingAs($user)->get(route('manual-labels.batch.create', ['order' => 'SX01']));
        $page->assertSee('3 / 3 / 0')->assertSee('3 / 1 / 2');

        $this->actingAs($user)->post(route('manual-labels.batch.store'), [
            'production_order' => 'SX01', 'counts' => [$orders[0]->id => 0, $orders[1]->id => 2], 'include_partial' => '1',
        ])->assertOk();
        $this->assertSame(3, (int) ManualLabelPrint::where('label_code', 'NH000002')->sum('copies_count'));
        $this->actingAs($user)->get(route('manual-labels.history', ['order_code' => 'SX01', 'label_code' => 'NH000002']))
            ->assertOk()->assertSee('NH000002')->assertDontSee('NH000001');
        $this->actingAs($user)->post(route('manual-labels.batch.store'), [
            'production_order' => 'SX01', 'counts' => [$orders[0]->id => 1, $orders[1]->id => 0], 'include_partial' => '1',
        ])->assertSessionHasErrors('counts');
        $this->actingAs($user)->post(route('manual-labels.batch.store'), [
            'production_order' => 'SX01', 'counts' => [$orders[0]->id => 1, $orders[1]->id => 0], 'include_partial' => '1', 'allow_extra' => '1',
        ])->assertOk();
        $this->actingAs($user)->delete(route('manual-labels.orders.destroy', $orders[0]))->assertSessionHasErrors('order');
        $this->actingAs($user)->put(route('manual-labels.orders.update', $orders[0]), [
            'production_order' => 'SX01', 'name' => 'Bạch Thược', 'batch' => 'OTHER', 'pack_spec' => '3 kg', 'quantity' => '10 kg', 'label_type' => 'vt',
        ])->assertSessionHasErrors('name');
    }

    public function test_manual_orders_accept_numeric_kg_values_and_print_uppercase_units(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        \App\Models\ManualLabelProduct::create([
            'name' => 'Bạch Thược', 'name_key' => \App\Models\ManualLabelProduct::key('Bạch Thược'), 'category' => 'Vị thuốc',
        ]);
        $base = ['name' => 'Bạch Thược', 'batch' => 'LOT1', 'mfg_date' => '2026-01-01', 'exp_date' => '2028-01-01', 'label_type' => 'vt'];
        $this->actingAs($user)->post(route('manual-labels.orders.store'), [
            'production_order' => 'SX-KG',
            'lines' => [
                $base + ['pack_spec' => '1', 'quantity' => '2'],
                $base + ['pack_spec' => '2', 'quantity' => '4'],
                $base + ['pack_spec' => '0.5', 'quantity' => '1'],
            ],
        ])->assertRedirect(route('manual-labels.orders'));

        $orders = \App\Models\ManualLabelProductionOrder::orderBy('id')->get();
        $this->assertSame(['1 KG', '2 KG', '0.5 KG'], $orders->pluck('pack_spec')->all());
        $this->assertSame(['2 KG', '4 KG', '1 KG'], $orders->pluck('quantity')->all());
        $this->assertSame([2, 2, 2], $orders->pluck('label_count')->all());

        $page = $this->actingAs($user)->get(route('manual-labels.batch.create', ['order' => 'SX-KG']));
        $page->assertOk()->assertSee('2 KG / 1 KG')->assertSee('4 KG / 2 KG')->assertSee('1 KG / 0.5 KG');
        $response = $this->actingAs($user)->post(route('manual-labels.batch.store'), [
            'production_order' => 'SX-KG',
            'counts' => $orders->mapWithKeys(fn ($order) => [$order->id => 2])->all(),
            'include_partial' => '1',
        ]);
        $response->assertOk()->assertSee('1 KG')->assertSee('2 KG')->assertSee('0.5 KG')->assertDontSee('0.5 kg');
        $this->assertSame(2, (int) \App\Models\ManualLabelPrint::where('label_code', 'NH000003')->sum('copies_count'));
    }

    public function test_order_lines_save_individual_qr_content_for_print_and_reprint(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        \App\Models\ManualLabelProduct::create([
            'name' => 'Bạch Thược', 'name_key' => \App\Models\ManualLabelProduct::key('Bạch Thược'), 'category' => 'Vị thuốc',
        ]);
        $line = [
            'name' => 'Bạch Thược', 'batch' => 'LOT1', 'mfg_date' => '2026-01-01', 'exp_date' => '2028-01-01',
            'pack_spec' => '1', 'quantity' => '1', 'label_type' => 'vt',
        ];
        $this->actingAs($user)->post(route('manual-labels.orders.store'), [
            'production_order' => 'SX-QR',
            'lines' => [$line + ['qr_content' => 'https://example.com/first'], $line + ['qr_content' => ''], $line + ['qr_content' => 'second']],
        ])->assertRedirect(route('manual-labels.orders'));

        $orders = \App\Models\ManualLabelProductionOrder::orderBy('id')->get();
        $this->assertSame(['https://example.com/first', null, 'second'], $orders->pluck('qr_content')->all());
        $this->actingAs($user)->get(route('manual-labels.orders.edit', $orders[0]))
            ->assertOk()->assertSee('value="https://example.com/first"', false);
        $this->actingAs($user)->get(route('manual-labels.create', ['order' => $orders[0]->id]))
            ->assertOk()->assertSee('value="https://example.com/first"', false);
        $this->actingAs($user)->getJson(route('manual-labels.sources.search', ['q' => 'NH000003']))
            ->assertJsonPath('0.qr_content', 'second');

        $response = $this->actingAs($user)->post(route('manual-labels.batch.store'), [
            'production_order' => 'SX-QR', 'counts' => $orders->mapWithKeys(fn ($order) => [$order->id => 1])->all(), 'include_partial' => '1',
        ]);
        $response->assertOk()->assertSee('data-qr-value="https://example.com/first"', false)->assertSee('data-qr-value="second"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'data-qr-value='));
        $print = ManualLabelPrint::where('label_code', $orders[0]->label_code)->firstOrFail();
        $this->assertSame('https://example.com/first', $print->qr_content);

        $this->actingAs($user)->put(route('manual-labels.orders.update', $orders[0]), $line + [
            'production_order' => 'SX-QR', 'qr_content' => 'updated',
        ])->assertRedirect(route('manual-labels.orders'));
        $this->assertSame('updated', $orders[0]->fresh()->qr_content);
        $this->actingAs($user)->post(route('manual-labels.reprint', $print))
            ->assertOk()->assertSee('data-qr-value="https://example.com/first"', false)->assertDontSee('data-qr-value="updated"', false);
    }

    public function test_order_line_qr_content_must_not_exceed_print_limit(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $this->actingAs($user)->post(route('manual-labels.orders.store'), [
            'production_order' => 'SX-QR',
            'lines' => [['name' => 'A', 'batch' => 'LOT1', 'pack_spec' => '1', 'quantity' => '1', 'label_type' => 'vt', 'qr_content' => str_repeat('a', 1001)]],
        ])->assertSessionHasErrors('lines.0.qr_content');
        $this->assertDatabaseCount('manual_label_production_orders', 0);
    }

    public function test_order_lines_store_edit_and_print_storage_instructions(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        \App\Models\ManualLabelProduct::create([
            'name' => 'Bạch Thược', 'name_key' => \App\Models\ManualLabelProduct::key('Bạch Thược'), 'category' => 'Vị thuốc',
        ]);
        $line = [
            'name' => 'Bạch Thược', 'batch' => 'LOT1', 'mfg_date' => '2026-01-01', 'exp_date' => '2028-01-01',
            'pack_spec' => '1', 'quantity' => '1', 'label_type' => 'vt',
        ];
        $this->actingAs($user)->get(route('manual-labels.orders.create'))
            ->assertOk()->assertSee('line.storage', false);
        $this->actingAs($user)->post(route('manual-labels.orders.store'), [
            'production_order' => 'SX-STORAGE', 'lines' => [$line, $line + ['storage' => 'Bảo quản lạnh']],
        ])->assertRedirect(route('manual-labels.orders'));

        $orders = \App\Models\ManualLabelProductionOrder::orderBy('id')->get();
        $default = \App\Models\ManualLabelProductionOrder::DEFAULT_STORAGE;
        $this->assertSame([$default, 'Bảo quản lạnh'], $orders->pluck('storage')->all());
        $this->actingAs($user)->get(route('manual-labels.orders.edit', $orders[1]))->assertOk()->assertSee('Bảo quản lạnh');
        $this->actingAs($user)->get(route('manual-labels.create', ['order' => $orders[0]->id]))->assertOk()->assertSee($default);
        $this->actingAs($user)->getJson(route('manual-labels.sources.search', ['q' => 'NH000002']))
            ->assertJsonPath('0.storage', 'Bảo quản lạnh');
        $response = $this->actingAs($user)->post(route('manual-labels.batch.store'), [
            'production_order' => 'SX-STORAGE', 'counts' => $orders->mapWithKeys(fn ($order) => [$order->id => 1])->all(), 'include_partial' => '1',
        ]);
        $response->assertOk()->assertSee($default)->assertSee('Bảo quản lạnh');
        $print = ManualLabelPrint::where('label_code', $orders[1]->label_code)->firstOrFail();
        $this->assertSame('Bảo quản lạnh', $print->storage);

        $this->actingAs($user)->put(route('manual-labels.orders.update', $orders[1]), $line + [
            'production_order' => 'SX-STORAGE', 'storage' => 'Nơi khô',
        ])->assertRedirect(route('manual-labels.orders'));
        $this->assertSame('Nơi khô', $orders[1]->fresh()->storage);
        $this->actingAs($user)->post(route('manual-labels.reprint', $print))
            ->assertOk()->assertSee('Bảo quản lạnh')->assertDontSee('Nơi khô');
        $this->actingAs($user)->put(route('manual-labels.orders.update', $orders[1]), $line + [
            'production_order' => 'SX-STORAGE', 'storage' => '',
        ])->assertRedirect(route('manual-labels.orders'));
        $this->assertNull($orders[1]->fresh()->storage);
    }

    public function test_order_line_storage_must_not_exceed_print_limit(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $this->actingAs($user)->post(route('manual-labels.orders.store'), [
            'production_order' => 'SX-STORAGE',
            'lines' => [['name' => 'A', 'batch' => 'LOT1', 'pack_spec' => '1', 'quantity' => '1', 'label_type' => 'vt', 'storage' => str_repeat('a', 256)]],
        ])->assertSessionHasErrors('lines.0.storage');
        $this->assertDatabaseCount('manual_label_production_orders', 0);
    }

    public function test_batch_print_rejects_bad_or_changed_lines_without_recording_any_prints(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $product = \App\Models\ManualLabelProduct::create(['name' => 'A', 'name_key' => 'a', 'category' => 'Vị thuốc']);
        $order = \App\Models\ManualLabelProductionOrder::create([
            'label_code' => 'NH000001', 'production_order' => 'SX01', 'name' => 'A', 'batch' => 'LOT1',
            'mfg_date' => '2026-01-01', 'exp_date' => '2028-01-01', 'pack_spec' => '3 kg', 'quantity' => '10 kg',
        ]);
        $this->actingAs($user)->post(route('manual-labels.batch.store'), [
            'production_order' => 'SX01', 'counts' => [$order->id => 3, 999 => 1], 'include_partial' => '1',
        ])->assertSessionHasErrors('counts');
        $product->delete();
        $this->actingAs($user)->post(route('manual-labels.batch.store'), [
            'production_order' => 'SX01', 'counts' => [$order->id => 3], 'include_partial' => '1',
        ])->assertSessionHasErrors('counts');
        $this->assertDatabaseCount('manual_label_prints', 0);
        $blocked = User::factory()->create(['role' => 'production', 'can_print_labels' => false]);
        $this->actingAs($blocked)->get(route('manual-labels.batch.create', ['order' => 'SX01']))->assertForbidden();
        $this->actingAs($blocked)->post(route('manual-labels.batch.store'), [])->assertForbidden();
    }

    public function test_existing_codes_and_history_are_normalized_without_reusing_numbers(): void
    {
        $order = \App\Models\ManualLabelProductionOrder::create([
            'label_code' => 'NH185', 'production_order' => 'SX01', 'name' => 'A',
        ]);
        ManualLabelPrint::create($this->payload(['label_code' => 'NH185']));
        \Illuminate\Support\Facades\Schema::drop('manual_label_sequences');
        $migration = require database_path('migrations/2026_11_16_170000_normalize_manual_label_codes.php');
        $migration->up();
        $this->assertSame('NH000185', $order->fresh()->label_code);
        $this->assertDatabaseHas('manual_label_prints', ['label_code' => 'NH000185']);
        $this->assertSame('NH000186', \Illuminate\Support\Facades\DB::transaction(fn () => \App\Services\ManualLabelOrders::nextCode()));
    }

    public function test_conversion_stops_before_changing_colliding_old_codes(): void
    {
        \App\Models\ManualLabelProductionOrder::create(['label_code' => 'NH1', 'name' => 'A']);
        \App\Models\ManualLabelProductionOrder::create(['label_code' => 'NH001', 'name' => 'B']);
        \Illuminate\Support\Facades\Schema::drop('manual_label_sequences');
        $migration = require database_path('migrations/2026_11_16_170000_normalize_manual_label_codes.php');
        try {
            $migration->up();
            $this->fail('Colliding codes must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('trùng', $exception->getMessage());
        }
        $this->assertDatabaseHas('manual_label_production_orders', ['label_code' => 'NH1']);
        $this->assertDatabaseHas('manual_label_production_orders', ['label_code' => 'NH001']);
    }
}
