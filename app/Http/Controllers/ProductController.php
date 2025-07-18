<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Size;
use App\Models\Color;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\StockTransaction;
use App\Traits\GeneratesUniqueCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;




class ProductController extends Controller
{
    use GeneratesUniqueCode;

    public function test(Request $request)
    {
        $allcategories = Category::with('parent')->get()->map(function ($category) {
            $category->hierarchy_string = $category->hierarchy_string; // Access it
            return $category;
        });
        return Inertia::render('Products/index2', [
            'categories' => $allcategories
        ]);
    }

    public function fetchProducts(Request $request)
    {
        $query = $request->input('search');
        $sortOrder = $request->input('sort');
        $selectedColor = $request->input('color');
        $selectedSize = $request->input('size');
        $stockStatus = $request->input('stockStatus');
        $selectedCategory = $request->input('selectedCategory');

        $productsQuery = Product::with('category', 'color', 'size', 'supplier')
         ->whereNotNull('name')
            ->when($query, function ($queryBuilder) use ($query) {
                $queryBuilder->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%");
                });
            })
            ->when($selectedColor, function ($queryBuilder) use ($selectedColor) {
                $queryBuilder->whereHas('color', function ($colorQuery) use ($selectedColor) {
                    $colorQuery->where('name', $selectedColor);
                });
            })
            ->when($selectedSize, function ($queryBuilder) use ($selectedSize) {
                $queryBuilder->whereHas('size', function ($sizeQuery) use ($selectedSize) {
                    $sizeQuery->where('name', $selectedSize);
                });
            })
            ->when(in_array($sortOrder, ['asc', 'desc']), function ($queryBuilder) use ($sortOrder) {
                $queryBuilder->orderBy('selling_price', $sortOrder);
            })
            ->when($stockStatus, function ($queryBuilder) use ($stockStatus) {
                if ($stockStatus === 'in') {
                    $queryBuilder->where('stock_quantity', '>', 0);
                } elseif ($stockStatus === 'out') {
                    $queryBuilder->where('stock_quantity', '<=', 0);
                }
            })
            ->when($selectedCategory, function ($queryBuilder) use ($selectedCategory) {
                $queryBuilder->where('category_id', $selectedCategory);
            });

        $products = $productsQuery->orderBy('created_at', 'desc')->paginate(8);

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    $query = $request->input('search');
    $sortOrder = $request->input('sort');
    $selectedColor = $request->input('color');
    $selectedSize = $request->input('size');
    $stockStatus = $request->input('stockStatus');
    $selectedCategory = $request->input('selectedCategory');

    $rawProducts = Product::with(['category', 'color', 'size', 'supplier'])

     ->whereNotNull('name')
        ->when($query, fn($q) => $q->where(fn($sub) =>
            $sub->where('name', 'like', "%{$query}%")
                 ->orWhere('code', 'like', "%{$query}%")))
        ->when($selectedColor, fn($q) => $q->whereHas('color', fn($c) => $c->where('name', $selectedColor)))
        ->when($selectedSize, fn($q) => $q->whereHas('size', fn($s) => $s->where('name', $selectedSize)))
        ->when($selectedCategory, fn($q) => $q->where('category_id', $selectedCategory))
        ->when($stockStatus, function ($q) use ($stockStatus) {
            if ($stockStatus === 'in') {
                $q->where('stock_quantity', '>', 0);
            } elseif ($stockStatus === 'out') {
                $q->where('stock_quantity', '<=', 0);
            }
        })
        ->orderByDesc('id')
        ->get();



    // Filter to get only one record per code with stock > 0 (oldest first)
    $filteredProducts = $rawProducts->groupBy('code')->map(function ($group) {
        return $group->firstWhere('stock_quantity', '>', 0) ?? $group->first();
    })->values(); // `values()` to reset array indexes

    // Apply sort by price if specified
    if (in_array($sortOrder, ['asc', 'desc'])) {
        $filteredProducts = $filteredProducts->sortBy('selling_price', SORT_REGULAR, $sortOrder === 'desc')->values();
    }

    // Paginate manually using Laravel’s LengthAwarePaginator
    $perPage = 8;
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $pagedProducts = new LengthAwarePaginator(
        $filteredProducts->forPage($currentPage, $perPage),
        $filteredProducts->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    // Load other data
    $allcategories = Category::with('parent')->get()->map(function ($category) {
        $category->hierarchy_string = $category->hierarchy_string;
        return $category;
    });

    $preOrderProducts = Product::with(['category', 'supplier', 'color', 'size'])
        ->whereColumn('total_quantity', '<=', 'preorder_level_qty')
        ->get();
    $preOrderAlertCount = $preOrderProducts->count();


$expiryProducts = Product::with(['category', 'supplier'])
    ->whereNotNull('expire_date')
    ->get()
    ->map(function ($product) {
        $product->days_left = Carbon::now()->diffInDays(Carbon::parse($product->expire_date), false);
        $product->within_margin = $product->days_left <= $product->expiry_date_margin;
        return $product;
    })
    ->filter(fn ($p) => $p->within_margin)
    ->values(); // 👈 Important to reset keys

$expiryAlertCount = $expiryProducts->count();




    return Inertia::render('Products/Index', [
        'products' => $pagedProducts,
        'rawProducts' => $rawProducts,
        'allcategories' => $allcategories,
        'colors' => Color::latest()->get(),
        'sizes' => Size::latest()->get(),
        'suppliers' => Supplier::latest()->get(),
        'totalProducts' => $filteredProducts->count(),
        'search' => $query,
        'sort' => $sortOrder,
        'color' => $selectedColor,
        'size' => $selectedSize,
        'stockStatus' => $stockStatus,
        'preOrderAlertCount' => $preOrderAlertCount,
        'preOrderProducts' => $preOrderProducts,
        'selectedCategory' => $selectedCategory,
        'expiryProducts' => $expiryProducts,
        'expiryAlertCount' => $expiryAlertCount,
    ]);
}


    /**
     * Show the form for creating a new resource.
     */
    // public function create()
    // {
    //     $categories = Category::all();
    //     $products = Product::all();
    //     $suppliers = Supplier::all();
    //     $colors = Color::all();
    //     $sizes = Size::all();



    //     return Inertia::render('Products/Create', [
    //         'products' => $products,
    //         'categories' => $categories,
    //         'suppliers' => $suppliers,
    //         'colors' => $colors,
    //         'sizes' => $sizes,
    //     ]);
    // }

    /**
     * Store a newly created resource in storage.
     */




  public function store(Request $request)
    {


        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|max:50',
            // 'code' => [
            //     'string',
            //     'max:50',
            //     Rule::unique('products')->whereNull('deleted_at'),
            // ],
            'size_id' => 'nullable|exists:sizes,id',
            'color_id' => 'nullable|exists:colors,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->input('cost_price')) {
                        $fail('The selling price must be greater than or equal to the cost price.');
                    }
                },
            ],
            'discounted_price' => 'nullable|numeric|min:0',
              'discount' => 'nullable|numeric|min:0|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'preorder_level_qty' => 'nullable|integer|min:0',

            'supplier_id' => 'nullable|exists:suppliers,id',
            'barcode' => 'nullable|string|unique:products',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'expire_date' => 'nullable|date',
           'expiry_date_margin' => 'nullable|integer|min:0',
            'batch_no' => 'nullable|max:50',
            'purchase_date' => 'nullable|date',


               'whole_price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->input('cost_price')) {
                        $fail('The selling price must be greater than or equal to the cost price.');
                    }
                },
            ],
            'wholesale_discount' => 'nullable|numeric|min:0|max:100',
            'final_whole_price' => 'nullable|numeric|min:0',
             'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',

        ]);

        // dd($validated);

        try {
            // Handle image upload
            if ($request->hasFile('image')) {
                $fileExtension = $request->file('image')->getClientOriginalExtension();
                $fileName = 'product_' . date("YmdHis") . '.' . $fileExtension;
                $path = $request->file('image')->storeAs('products', $fileName, 'public');
                $validated['image'] = 'storage/' . $path;
            }

            if (empty($validated['barcode'])) {
                $validated['barcode'] = $this->generateUniqueCode(12);
            }



if ($request->hasFile('certificate')) {
    $fileExtension = $request->file('certificate')->getClientOriginalExtension();
    $fileName = 'certificate_' . date("YmdHis") . '.' . $fileExtension;
    $path = $request->file('certificate')->storeAs('certificates', $fileName, 'public'); // folder: certificates (plural is better)
    $validated['certificate_path'] = 'storage/' . $path;
}



            $validated['total_quantity'] = $validated['stock_quantity'] ?? 0;
            // Create the product
            $product = Product::create($validated);
            // $product->update(['code' => 'PROD-' . $product->id]);

            // Add stock transaction if stock quantity is provided
            $stockQuantity = $validated['stock_quantity'] ?? 0; // Default to 0 if not provided
            if ($stockQuantity > 0) {
                StockTransaction::create([
                    'product_id' => $product->id,
                    'transaction_type' => 'Added',
                    'quantity' => $stockQuantity,
                    'transaction_date' => now(),
                    'supplier_id' => $validated['supplier_id'] ?? null,
                ]);
            }

            // Redirect with success message
            return redirect()->route('products.index')->banner('Product created successfully');
        } catch (\Exception $e) {

            \Log::error('Error creating product: ' . $e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while creating the product. Please try again.');
        }
    }
















public function productVariantStore(Request $request)
{
    if (!Gate::allows('hasRole', ['Admin'])) {
        abort(403, 'Unauthorized');
    }

    $validated = $request->validate([
        'category_id' => 'nullable|exists:categories,id',
        'name' => 'required|string|max:255',
        'code' => [
            'nullable',
            'string',
            'max:50',
            Rule::unique('products')->whereNull('deleted_at'),
        ],
        'barcode' => [
            'nullable',
            'string',
            Rule::unique('products')->whereNull('deleted_at'),
        ],
        'size_id' => 'nullable|exists:sizes,id',
        'color_id' => 'nullable|exists:colors,id',
        'cost_price' => 'nullable|numeric|min:0',
        'selling_price' => [
            'nullable',
            'numeric',
            'min:0',
            function ($attribute, $value, $fail) use ($request) {
                if ($value < ($request->input('cost_price') ?? 0)) {
                    $fail('The selling price must be greater than or equal to the cost price.');
                }
            },
        ],
        'discounted_price' => 'nullable|numeric|min:0',
        'stock_quantity' => 'nullable|integer|min:0',
        'discount' => 'nullable|numeric|min:0|max:100',
        'supplier_id' => 'nullable|exists:suppliers,id',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        'expire_date' => 'nullable|date',
        'expiry_date_margin' => 'nullable|integer|min:0',
        'preorder_level_qty' => 'nullable|integer|min:0',
        'purchase_date' => 'nullable|date',
        'batch_no' => 'nullable|string|max:50',
         'whole_price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->input('cost_price')) {
                        $fail('The selling price must be greater than or equal to the cost price.');
                    }
                },
            ],
            'wholesale_discount' => 'nullable|numeric|min:0|max:100',
            'final_whole_price' => 'nullable|numeric|min:0',
               'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
    ]);

    try {
        // Handle image upload
        if ($request->hasFile('image')) {
            $fileExtension = $request->file('image')->getClientOriginalExtension();
            $fileName = 'product_' . date("YmdHis") . '.' . $fileExtension;
            $path = $request->file('image')->storeAs('products', $fileName, 'public');
            $validated['image'] = 'storage/' . $path;
        }


        // Generate barcode if not provided
        if (empty($validated['barcode'])) {
            $validated['barcode'] = $this->generateUniqueBarcode();
        }

        if ($request->hasFile('certificate')) {
    $certificatePath = $request->file('certificate')->store('certificates', 'public');
    $product->certificate_path = $certificatePath;
}


        // Create product
        $product = Product::create($validated);

        // Add stock transaction if stock quantity exists
        $stockQuantity = $validated['stock_quantity'] ?? 0;
        if ($stockQuantity > 0) {
            StockTransaction::create([
                'product_id' => $product->id,
                'transaction_type' => 'Added',
                'quantity' => $stockQuantity,
                'transaction_date' => now(),
                'supplier_id' => $validated['supplier_id'] ?? null,
            ]);
        }

        return redirect()->route('products.index')->banner('Product created successfully');
    } catch (\Exception $e) {
        Log::error('Error creating product: ' . $e->getMessage());
        return redirect()->back()->with('error', 'An error occurred while creating the product. Please try again.');
    }
}

// Helper to generate unique barcode
private function generateUniqueBarcode($length = 12)
{
    do {
        $barcode = strtoupper(Str::random($length));
    } while (Product::where('barcode', $barcode)->exists());

    return $barcode;
}


    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }
        // $categories = Category::all();
        // $sizes = Size::all();
        // $suppliers = Supplier::all();
        // $colors = Color::all();
        $categories = Category::orderBy('created_at', 'desc')->get();
        $colors = Color::orderBy('created_at', 'desc')->get();
        $sizes = Size::orderBy('created_at', 'desc')->get();
        $suppliers = Supplier::orderBy('created_at', 'desc')->get();

        $product->load('category', 'color', 'size', 'suppliers');

        return Inertia::render('Products/Show', [

            'categories' => $categories,
            'product' => $product,
            'suppliers' => $suppliers,
            'colors' => $colors,
            'sizes' => $sizes,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = Category::orderBy('created_at', 'desc')->get();
        $colors = Color::orderBy('created_at', 'desc')->get();
        $sizes = Size::orderBy('created_at', 'desc')->get();
        $suppliers = Supplier::orderBy('created_at', 'desc')->get();

        return inertia('Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'colors' => $colors,
            'sizes' => $sizes,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */

 public function update(Request $request, Product $product)
{
    if (!Gate::allows('hasRole', ['Admin'])) {
        abort(403, 'Unauthorized');
    }

    $validated = $request->validate([
        'category_id'           => 'nullable|exists:categories,id',
        'name'                  => 'required|string|max:255',
        'size_id'               => 'nullable|exists:sizes,id',
        'color_id'              => 'nullable|exists:colors,id',
        'cost_price'            => 'required|numeric|min:0',
        'selling_price'         => 'required|numeric|min:0',
        'discounted_price'      => 'nullable|numeric|min:0',
        'discount'              => 'nullable|numeric|min:0|max:100',
        'stock_quantity'        => 'nullable|integer|min:0',
        'supplier_id'           => 'nullable|exists:suppliers,id',
        'image'                 => 'nullable|image|max:2048',
        'certificate'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'expire_date'           => 'nullable|date',
        'expiry_date_margin'    => 'nullable|integer|min:0',
        'preorder_level_qty'    => 'nullable|integer|min:0',
        'batch_no'              => 'nullable|string|max:50',
        'purchase_date'         => 'nullable|date',
        'whole_price'           => 'required|numeric|min:0',
        'final_whole_price'     => 'nullable|numeric|min:0',
        'wholesale_discount'    => 'nullable|numeric|min:0|max:100',
    ]);

    // Handle image upload
    if ($request->hasFile('image')) {
        // Delete old image if exists
        if ($product->image && Storage::disk('public')->exists(str_replace('storage/', '', $product->image))) {
            Storage::disk('public')->delete(str_replace('storage/', '', $product->image));
        }

        $fileName = 'product_' . now()->format('YmdHis') . '.' . $request->file('image')->getClientOriginalExtension();
        $path = $request->file('image')->storeAs('products', $fileName, 'public');
        $validated['image'] = 'storage/' . $path;
    } else {
        $validated['image'] = $product->image;
    }

    // Handle certificate upload
    if ($request->hasFile('certificate')) {
        if ($product->certificate_path && Storage::disk('public')->exists($product->certificate_path)) {
            Storage::disk('public')->delete($product->certificate_path);
        }

        $certificatePath = $request->file('certificate')->store('certificates', 'public');
        $validated['certificate_path'] = $certificatePath;
    } else {
        $validated['certificate_path'] = $product->certificate_path;
    }

    // Stock quantity and transaction
    $validated['total_quantity'] = $validated['stock_quantity'] ?? $product->stock_quantity;
    $stockChange = ($validated['stock_quantity'] ?? $product->stock_quantity) - $product->stock_quantity;

    $product->update($validated);

    if ($stockChange !== 0) {
        $transactionType = $stockChange > 0 ? 'Added' : 'Deducted';

        StockTransaction::create([
            'product_id'       => $product->id,
            'transaction_type' => $transactionType,
            'quantity'         => abs($stockChange),
            'transaction_date' => now(),
            'supplier_id'      => $validated['supplier_id'] ?? null,
        ]);
    }

    return redirect()->route('products.index')->with('banner', 'Product updated successfully');
}






    public function destroy(Product $product)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        // Prepare to delete the image
        $imagePath = str_replace('storage/', '', $product->image);
        $imageUsageCount = Product::where('image', $product->image)
            ->where('id', '!=', $product->id)
            ->count();

        if ($imageUsageCount === 0 && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        try {
            // Log the stock transaction
            StockTransaction::create([
                'product_id' => $product->id,
                'transaction_type' => 'Deleted',
                'quantity' => $product->stock_quantity ?? 0, // Fallback to 0 if undefined
                'transaction_date' => now(),
                'supplier_id' => $product->supplier_id ?? null, // Handle potential null value
            ]);
        } catch (\Exception $e) {
            // Log error and return a failure message
            report($e);
            return redirect()->route('products.index')->withErrors('Failed to log stock transaction. Please try again.');
        }

        // Delete the product
        $product->delete();

        return redirect()->route('products.index')->banner('Product Deleted successfully.');
    }


    public function getNextBatchNo(Request $request)
    {
        $code = $request->input('code');

        $latestBatch = Product::where('code', $code)
            ->orderBy('created_at', 'desc')
            ->pluck('batch_no')
            ->filter()
            ->map(function ($batch) {
                if (preg_match('/batch(\d+)/i', $batch, $matches)) {
                    return (int)$matches[1];
                }
                return 0;
            })
            ->max();

        $nextBatch = 'batch' . (($latestBatch ?? 0) + 1);

        return response()->json(['next_batch_no' => $nextBatch]);
    }

    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');

        $data = array_map('str_getcsv', file($file));

        // Normalize header row
        $headers = array_map('strtolower', array_map('trim', $data[0]));
        unset($data[0]);

        foreach ($data as $row) {
            $row = array_map('trim', $row);

            // Skip empty rows
            if (count(array_filter($row)) === 0) {
                continue;
            }

            Product::create([
                'name'                => $row[0] ?? null,
                'code'                => $row[1] ?? null,
                'cost_price'          => is_numeric($row[2]) ? $row[2] : 0,
                'selling_price'       => is_numeric($row[3]) ? $row[3] : 0,
                'discount'            => is_numeric($row[4]) ? $row[4] : 0,
                'discounted_price'    => is_numeric($row[5]) ? $row[5] : 0,
                'stock_quantity'      => is_numeric($row[6]) ? $row[6] : 0,
                'purchase_date'       => !empty($row[7]) ? Carbon::parse($row[7]) : null,
                'expire_date'         => !empty($row[8]) ? Carbon::parse($row[8]) : null,
                'barcode'             => $row[9] ?? null,
                'batch_no'            => $row[10] ?? null,
                'preorder_level_qty'  => is_numeric($row[11] ?? null) ? $row[11] : 0,
                'expiry_date_margin'       => is_numeric($row[12] ?? null) ? $row[12] : 0,
                'whole_price'         => is_numeric($row[13] ?? null) ? $row[13] : 0,
                'wholesale_discount'  => is_numeric($row[14] ?? null) ? $row[14] : 0,
                'final_whole_price'   => is_numeric($row[15] ?? null) ? $row[15] : 0,
            ]);
        }

        return back()->with('success', 'CSV uploaded and products saved successfully.');
    }


}
