<?php

namespace App\Http\Controllers;

use App\Models\ProductionFinishedBatch;
use App\Models\SupplierBatch;
use Illuminate\Support\Facades\Storage;

class PrivateDocumentController extends Controller
{
    public function supplierCoa(SupplierBatch $supplierBatch)
    {
        return $this->serve($supplierBatch->coa_file, 'coas');
    }

    public function productionQualityReport(ProductionFinishedBatch $productionFinishedBatch)
    {
        return $this->serve($productionFinishedBatch->qc_test_report_file, 'qc-reports');
    }

    private function serve(?string $path, string $directory)
    {
        abort_unless(
            $path
                && str_starts_with($path, $directory . '/')
                && ! str_contains($path, '..')
                && Storage::disk('local')->exists($path),
            404
        );

        $contentType = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => abort(404),
        };

        $response = response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
