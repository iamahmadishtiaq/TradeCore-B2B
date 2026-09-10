<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();

        $query = Product::published()
            ->with(['vendor', 'category', 'variants.priceTiers'])
            ->latest();

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(12)->withQueryString();

        return view('catalog.index', compact('products', 'categories'));
    }

    public function show(string $slug): View
    {
        $product = Product::published()
            ->where('slug', $slug)
            ->with([
                'vendor',
                'category',
                'variants.priceTiers',
                'variants.inventoryBatches' => function ($q) {
                    $q->whereHas('warehouse', fn ($w) => $w->where('is_active', true));
                }
            ])
            ->firstOrFail();

        return view('catalog.show', compact('product'));
    }
}