<?php

namespace App\Http\Controllers;

use App\Models\Excipient;

class ExcipientController extends CatalogController
{
    protected function modelClass(): string
    {
        return Excipient::class;
    }

    protected function routePrefix(): string
    {
        return 'excipients';
    }

    protected function materialType(): string
    {
        return 'excipient';
    }

    protected function labels(): array
    {
        return [
            'title' => 'Danh mục Tá dược',
            'singular' => 'tá dược',
            'add' => 'Thêm tá dược',
            'created' => 'Thêm tá dược thành công!',
            'updated' => 'Cập nhật tá dược thành công!',
            'deleted' => 'Xóa tá dược thành công!',
        ];
    }
}
