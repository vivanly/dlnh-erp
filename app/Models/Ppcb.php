<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ppcb extends Model
{
    use HasFactory;

    protected $table = 'ppcb'; // Khai báo đúng tên bảng trong database

    protected $fillable = [
        'ma',
        'ten_ppcb',
        'chi_tiet_ppcb',
        'ghi_chu',
    ];
}