<?php

namespace App\Http\Controllers;

use App\Models\Accessory;

class AccessoryController extends CatalogController
{
    protected function modelClass(): string
    {
        return Accessory::class;
    }

    protected function routePrefix(): string
    {
        return 'accessories';
    }

    protected function materialType(): string
    {
        return 'accessory';
    }

    protected function labels(): array
    {
        return [
            'title' => 'Danh mục Phụ liệu',
            'singular' => 'phụ liệu',
            'add' => 'Thêm phụ liệu',
            'created' => 'Thêm phụ liệu thành công!',
            'updated' => 'Cập nhật phụ liệu thành công!',
            'deleted' => 'Xóa phụ liệu thành công!',
        ];
    }
}
