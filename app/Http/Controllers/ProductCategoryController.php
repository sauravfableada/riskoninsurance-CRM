<?php

namespace App\Http\Controllers;

class ProductCategoryController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->hasMatrixPermission('view_products'), 403);

        return view('masters.product_categories.index');
    }
}
