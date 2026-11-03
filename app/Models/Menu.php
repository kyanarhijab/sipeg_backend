<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'route',
        'icon',
        'order_index',
        'is_active',
    ];

    /**
     * Ambil daftar menu aktif yang sudah diurutkan (Raw Query)
     */
    public static function getActiveMenus(): array
    {
        return DB::select("
            SELECT id, name, route, icon, order_index 
            FROM menus 
            WHERE is_active = 1 
            ORDER BY order_index ASC
        ");
    }
}