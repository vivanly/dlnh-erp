<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectorReadOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_directors_can_view_all_module_pages_without_write_access(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        $this->actingAs($director);

        foreach ([
            'dashboard',
            'suppliers.index',
            'customers.index',
            'purchase-orders.index',
            'orders.index',
            'departments.index',
            'users.index',
            'products.index',
            'ppcb.index',
            'boms.index',
            'boms.plan',
            'production-monthly-plans.index',
            'production-monthly-plans.approvals',
            'production-planning.index',
            'production-planning.approvals',
            'sales-order-approvals.index',
            'production-orders.index',
            'production.packaging.index',
            'warehouse.purchase-orders',
            'warehouse.sales-order-stock-checks',
            'warehouse.production-batches',
            'warehouse.sales-orders',
            'warehouse.stock',
            'qa.internal-lots.index',
            'qa.production-batches.index',
            'qa.production-batch-assignments.index',
            'qa.batches.index',
            'qa.coas.index',
            'product-regulatory-documents.index',
            'labels.index',
            'traceability.index',
            'traceability.management.index',
            'material-lots.index',
            'permissions.label-printing.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }

        $this->get(route('dashboard'))->assertSee('director-read-only');
        $this->patch(route('profile.update'), [])->assertForbidden();
        $this->post(route('accessories.store'), [])->assertForbidden();
    }

    public function test_director_roles_and_positions_can_use_board_approval_actions(): void
    {
        $roleDirector = User::factory()->create(['role' => 'bidding_director']);
        $positionDirector = User::factory()->create(['role' => 'staff', 'position' => 'Giám Đốc']);

        $this->assertTrue($roleDirector->isExecutiveBoardMember());
        $this->assertTrue($positionDirector->isExecutiveBoardMember());
        $this->assertTrue($positionDirector->canApprovePurchaseOrder());
        $this->assertTrue($positionDirector->canApproveSalesOrder());
        $this->assertTrue($positionDirector->canApproveProductionPlan());

        $this->actingAs($positionDirector)
            ->post(route('production-planning.approve', 999999), [])
            ->assertNotFound();
    }
}
