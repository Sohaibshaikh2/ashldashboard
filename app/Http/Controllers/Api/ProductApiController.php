<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
class ProductApiController extends Controller
{
    public function getRecentProducts()
    {
        // Sab se aakhri 6 products fetch karein (ID ke hisaab se descending)
        $products = Product::orderBy('id', 'desc')->take(6)->get();

        // In 6 products ki IDs nikal lein
        $productIds = $products->pluck('id');

        // Sirf inhi 6 products ki tasweerein fetch karein
        $images = ProductImage::whereIn('product_id', $productIds)->get();

        // Har product ke andar uski images ka array map kar dein
        $products->map(function ($product) use ($images) {
            // URL ko poora link banane ke liye asset() use karein agar zaroorat ho
            $product->images = $images->where('product_id', $product->id)->values();
            return $product;
        });

        // JSON response return karein
        return response()->json([
            'success' => true,
            'message' => 'Recent 6 products fetched successfully.',
            'data' => $products
        ], 200);
    }

    public function getAllProducts()
    {
        $products = Product::orderBy('id', 'desc')->get();
        $images = ProductImage::all();

        // Pivot tables aur attributes ka data fetch kar rahe hain
        $colorMaps = DB::table('product_color_map')->get();
        $colors = DB::table('product_color')->get();
        
        $sizeMaps = DB::table('product_size_map')->get();
        $sizes = DB::table('product_sizes')->get();
        
        $materialMaps = DB::table('product_material_map')->get();
        $materials = DB::table('product_material')->get();
        
        $finishMaps = DB::table('product_finish_map')->get();
        $finishes = DB::table('product_finish')->get();

        // Har product ke andar uski tasweerein aur attributes (arrays) shamil kar rahe hain
        $products->map(function ($product) use ($images, $colors, $colorMaps, $sizes, $sizeMaps, $materials, $materialMaps, $finishes, $finishMaps) {
            $product->images = $images->where('product_id', $product->id)->values();

            $cIds = $colorMaps->where('product_id', $product->id)->pluck('color_id');
            $product->colors = $colors->whereIn('id', $cIds)->pluck('name')->toArray();

            $sIds = $sizeMaps->where('product_id', $product->id)->pluck('size_id');
            $product->sizes = $sizes->whereIn('id', $sIds)->pluck('abriviation')->toArray();

            $mIds = $materialMaps->where('product_id', $product->id)->pluck('material_id');
            $product->materials = $materials->whereIn('id', $mIds)->pluck('name')->toArray();

            $fIds = $finishMaps->where('product_id', $product->id)->pluck('finish_id');
            $product->finishes = $finishes->whereIn('id', $fIds)->pluck('name')->toArray();

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'All products fetched successfully.',
            'data' => $products
        ], 200);
    }

    public function getAttributes()
    {
        return response()->json([
            'success' => true,
            'message' => 'Attributes fetched successfully.',
            'data' => [
                'sizes' => DB::table('product_sizes')->select('id', 'name', 'abriviation')->get(),
                'colors' => DB::table('product_color')->select('id', 'name')->get(),
                'materials' => DB::table('product_material')->select('id', 'name')->get(),
                'finishes' => DB::table('product_finish')->select('id', 'name')->get(),
            ]
        ], 200);
    }
}