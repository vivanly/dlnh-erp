<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;

class RawMaterialController extends CatalogController
{
    protected function modelClass(): string
    {
        return RawMaterial::class;
    }

    protected function routePrefix(): string
    {
        return 'raw-materials';
    }

    protected function materialType(): string
    {
        return 'raw_material';
    }

    protected function labels(): array
    {
        return [
            'title' => 'Danh mục Dược liệu thô',
            'singular' => 'dược liệu thô',
            'add' => 'Thêm dược liệu thô',
            'created' => 'Thêm dược liệu thô thành công!',
            'updated' => 'Cập nhật dược liệu thô thành công!',
            'deleted' => 'Xóa dược liệu thô thành công!',
        ];
    }
}
