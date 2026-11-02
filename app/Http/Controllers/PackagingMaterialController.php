<?php

namespace App\Http\Controllers;

use App\Models\PackagingMaterial;

class PackagingMaterialController extends CatalogController
{
    protected function modelClass(): string
    {
        return PackagingMaterial::class;
    }

    protected function routePrefix(): string
    {
        return 'packaging';
    }

    protected function materialType(): string
    {
        return 'packaging';
    }

    protected function labels(): array
    {
        return [
            'title' => 'Danh mục Bao bì',
            'singular' => 'bao bì',
            'add' => 'Thêm bao bì',
            'created' => 'Thêm bao bì thành công!',
            'updated' => 'Cập nhật bao bì thành công!',
            'deleted' => 'Xóa bao bì thành công!',
        ];
    }
}
