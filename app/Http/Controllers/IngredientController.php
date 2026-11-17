<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;

class IngredientController extends CatalogController
{
    protected function modelClass(): string
    {
        return Ingredient::class;
    }

    protected function routePrefix(): string
    {
        return 'ingredients';
    }

    protected function materialType(): string
    {
        return 'ingredient';
    }

    protected function labels(): array
    {
        return [
            'title' => 'Danh mục Nguyên liệu',
            'singular' => 'nguyên liệu',
            'add' => 'Thêm nguyên liệu',
            'created' => 'Thêm nguyên liệu thành công!',
            'updated' => 'Cập nhật nguyên liệu thành công!',
            'deleted' => 'Xóa nguyên liệu thành công!',
        ];
    }
}
