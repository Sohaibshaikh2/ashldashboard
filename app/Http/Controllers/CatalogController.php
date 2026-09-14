<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ProductSize;
use App\Models\ProductFinish;
use App\Models\ProductColor;
use App\Models\ProductMaterial;
use App\Models\ProductImage;

class CatalogController extends Controller
{
    private $models = [
        'products' => Product::class,
        'categories' => Category::class,
        'subcategories' => Subcategory::class,
        'product_sizes' => ProductSize::class,
        'product_finish' => ProductFinish::class,
        'product_color' => ProductColor::class,
        'product_material' => ProductMaterial::class,
        'product_images' => ProductImage::class,
    ];

    public function index()
    {
        return Inertia::render('CatalogManager', [
            'products' => Product::all(),
            'categories' => Category::all(),
            'subcategories' => Subcategory::all(),
            'product_sizes' => ProductSize::all(),
            'product_finish' => ProductFinish::all(),
            'product_color' => ProductColor::class,
            'product_color' => ProductColor::all(),
            'product_material' => ProductMaterial::all(),
            'product_images' => ProductImage::all(),
            
            // Pivot mappings
            'product_color_map' => DB::table('product_color_map')->get(),
            'product_size_map' => DB::table('product_size_map')->get(),
            'product_material_map' => DB::table('product_material_map')->get(),
            'product_finish_map' => DB::table('product_finish_map')->get(),
        ]);
    }

    public function store(Request $request, $table)
    {
        if (!array_key_exists($table, $this->models)) {
            abort(400, 'Invalid table identifier.');
        }

        $allowedColumns = [
            'products'         => ['name', 'dics', 'price', 'discount_price', 'sub_category_id'],
            'categories'       => ['name'],
            'subcategories'    => ['name', 'category_id'],
            'product_sizes'    => ['name', 'abriviation'],
            'product_finish'   => ['name'],
            'product_color'    => ['name'],
            'product_material' => ['name'],
            'product_images'   => ['url', 'alt', 'product_id'],
        ];

        $data = $request->only($allowedColumns[$table]);

        if ($table === 'products') {
            if ($request->filled('id')) {
                $product = Product::where('id', $request->id)->first();
                $product->update($data);
                $productId = $product->id;
            } else {
                $product = Product::create($data);
                $productId = $product->id;
            }

            // Sync Pivot Tables
            DB::table('product_color_map')->where('product_id', $productId)->delete();
            if ($request->has('colors')) {
                foreach(explode(',', $request->colors) as $cId) {
                    if($cId) DB::table('product_color_map')->insert(['product_id' => $productId, 'color_id' => $cId]);
                }
            }

            DB::table('product_size_map')->where('product_id', $productId)->delete();
            if ($request->has('sizes')) {
                foreach(explode(',', $request->sizes) as $sId) {
                    if($sId) DB::table('product_size_map')->insert(['product_id' => $productId, 'size_id' => $sId]);
                }
            }

            DB::table('product_material_map')->where('product_id', $productId)->delete();
            if ($request->has('materials')) {
                foreach(explode(',', $request->materials) as $mId) {
                    if($mId) DB::table('product_material_map')->insert(['product_id' => $productId, 'material_id' => $mId]);
                }
            }

            DB::table('product_finish_map')->where('product_id', $productId)->delete();
            if ($request->has('finishes')) {
                foreach(explode(',', $request->finishes) as $fId) {
                    if($fId) DB::table('product_finish_map')->insert(['product_id' => $productId, 'finish_id' => $fId]);
                }
            }

            // --- IMAGE UPLOAD LOGIC ---
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    // Save file to storage/app/public/products
                    $path = $image->store('products', 'public');
                    
                    // Save to database
                    DB::table('product_images')->insert([
                        'url' => '/storage/' . $path,
                        'alt' => $product->name,
                        'product_id' => $productId,
                    ]);
                }
            }

            return back()->with('success', 'Product saved with attributes and images.');
        }

        // Standard logic for other tables
        $modelClass = $this->models[$table];
        if ($request->filled('id')) {
            $modelClass::where('id', $request->id)->update($data);
        } else {
            $modelClass::create($data);
        }

        return back()->with('success', 'Record saved successfully.');
    }

    public function destroy($table, $id)
    {
        if (!array_key_exists($table, $this->models)) {
            abort(400, 'Invalid table identifier.');
        }

        // Handle specific related deletions for products
        if ($table === 'products') {
            DB::table('product_color_map')->where('product_id', $id)->delete();
            DB::table('product_size_map')->where('product_id', $id)->delete();
            DB::table('product_material_map')->where('product_id', $id)->delete();
            DB::table('product_finish_map')->where('product_id', $id)->delete();
            
            // Delete product images
            DB::table('product_images')->where('product_id', $id)->delete();
        }

        $this->models[$table]::where('id', $id)->delete();

        return back()->with('success', 'Record deleted successfully.');
    }
}