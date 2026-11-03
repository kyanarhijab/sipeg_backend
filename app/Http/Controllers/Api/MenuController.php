<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::getActiveMenus();

        return response()->json([
            'status' => 'success',
            'data'   => $menus,
        ]);
    }
}