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
            'title' => 'Danh mục Nguyên liệu thô',
            'singular' => 'nguyên liệu thô',
            'add' => 'Thêm nguyên liệu thô',
            'created' => 'Thêm nguyên liệu thô thành công!',
            'updated' => 'Cập nhật nguyên liệu thô thành công!',
            'deleted' => 'Xóa nguyên liệu thô thành công!',
        ];
    }
}
