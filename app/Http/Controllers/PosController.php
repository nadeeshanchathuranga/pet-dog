<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Color;
use App\Models\Cheque;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ReturnReason;
use App\Models\Size;
use App\Models\StockTransaction;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PosController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Cashier'])) {
            abort(403, 'Unauthorized');
        }

        $allcategories = Category::with('parent')->get()->map(function ($category) {
            $category->hierarchy_string = $category->hierarchy_string; // Access it
            return $category;
        });
        $returnReasons = ReturnReason::orderBy('created_at', 'desc')->get();
        $colors = Color::orderBy('created_at', 'desc')->get();
        $sizes = Size::orderBy('created_at', 'desc')->get();
        $allemployee = Employee::orderBy('created_at', 'desc')->get();

        // Render the page for GET requests
        return Inertia::render('Pos/Index', [
            'product' => null,
            'error' => null,
            'loggedInUser' => Auth::user(),
            'allcategories' => $allcategories,
            'allemployee' => $allemployee,
            'colors' => $colors,
            'returnReasons' => $returnReasons,
            'sizes' => $sizes,
        ]);
    }

    public function getProduct(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Cashier'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'barcode' => 'required',
        ]);

        $product = Product::where('barcode', $request->barcode)
            ->orWhere('code', $request->barcode)
            ->first();

        return response()->json([
            'product' => $product,
            'error' => $product ? null : 'Product not found',
        ]);
    }

    public function getCoupon(Request $request)
    {
        $request->validate(
            ['code' => 'required|string'],
            ['code.required' => 'The coupon code missing.', 'code.string' => 'The coupon code must be a valid string.']
        );

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json(['error' => 'Invalid coupon code.']);
        }

        if (!$coupon->isValid()) {
            return response()->json(['error' => 'Coupon is expired or has been fully used.']);
        }

        return response()->json(['success' => 'Coupon applied successfully.', 'coupon' => $coupon]);
    }

    // public function submit(Request $request)
    // {

    //     if (!Gate::allows('hasRole', ['Admin', 'Cashier'])) {
    //         abort(403, 'Unauthorized');
    //     }

    //     $customer = null;

    //     $products = $request->input('products');
    //     $totalAmount = collect($products)->reduce(function ($carry, $product) {
    //         return $carry + ($product['quantity'] * $product['selling_price']);
    //     }, 0);

    //     $totalCost = collect($products)->reduce(function ($carry, $product) {
    //         return $carry + ($product['quantity'] * $product['cost_price']);
    //     }, 0);

    //     $productDiscounts = collect($products)->reduce(function ($carry, $product) {
    //         if (isset($product['discount']) && $product['discount'] > 0 && isset($product['apply_discount']) && $product['apply_discount'] != false) {
    //             $discountAmount = ($product['selling_price'] - $product['discounted_price']) * $product['quantity'];
    //             return $carry + $discountAmount;
    //         }
    //         return $carry;
    //     }, 0);

    //     // Get coupon discount if applied
    //     $couponDiscount = isset($request->input('appliedCoupon')['discount']) ?
    //         floatval($request->input('appliedCoupon')['discount']) : 0;


    //     // Calculate total combined discount
    //     $totalDiscount = $productDiscounts + $couponDiscount ;

    //     DB::beginTransaction(); // Start a transaction

    //     try {
    //         // Save the customer data to the database
    //         if ($request->input('customer.contactNumber') || $request->input('customer.name') || $request->input('customer.email')) {

    //             $phone = $request->input('customer.countryCode') . $request->input('customer.contactNumber');
    //             $customer = Customer::where('email', $request->input('customer.email'))->first();
    //             $customer1 = Customer::where('phone', $phone)->first();

    //             if ($customer) {
    //                 $email = '';
    //             } else {
    //                 $email = $request->input('customer.email');
    //             }

    //             if ($customer1) {
    //                 $phone = '';
    //             }

    //             if (!empty($phone) || !empty($email) || !empty($request->input('customer.name'))) {
    //                 $customer = Customer::create([
    //                     'name' => $request->input('customer.name'),
    //                     'email' => $email,
    //                     'phone' => $phone,
    //                     'address' => $request->input('customer.address', ''), // Optional address
    //                     'member_since' => now()->toDateString(), // Current date
    //                     'loyalty_points' => 0, // Default value
    //                 ]);
    //             }
    //         }

    //         // Create the sale record
    //         $sale = Sale::create([
    //             'customer_id' => $customer ? $customer->id : null, // Nullable customer_id
    //             'employee_id' => $request->input('employee_id'),
    //             'user_id' => $request->input('userId'), // Logged-in user ID
    //             'order_id' => $request->input('orderid'),
    //             'total_amount' => $totalAmount, // Total amount of the sale
    //             'discount' => $totalDiscount, // Default discount to 0 if not provided
    //             'total_cost' => $totalCost,
    //             'payment_method' => $request->input('paymentMethod'), // Payment method from the request
    //             'sale_date' => now()->toDateString(), // Current date
    //             'cash' => $request->input('cash'),
    //             'is_whole' => $request->boolean('isWholesale'),
    //             'custom_discount' => $request->input('custom_discount'),
    //             'guide_name' => $request->input('guide_name'),
    //             'guide_comi' => $request->input('guide_comi'),
    //             'guide_cash' => $request->input('guide_cash'),
    //             'total_to_include_guide' => $request->input('total_to_include_guide'),
    //             'credit_bill' => $request->boolean('credit_bill'),
    //         ]);

    //         foreach ($products as $product) {
    //             // Check stock before saving sale items
    //             $productModel = Product::find($product['id']);
    //             if ($productModel) {
    //                 $newStockQuantity = $productModel->stock_quantity - $product['quantity'];

    //                 // Prevent stock from going negative
    //                 if ($newStockQuantity < 0) {
    //                     // Rollback transaction and return error
    //                     DB::rollBack();
    //                     return response()->json([
    //                         'message' => "Insufficient stock for product: {$productModel->name}
    //                         ({$productModel->stock_quantity} available)",
    //                     ], 423);
    //                 }

    //                 if ($productModel->expire_date && now()->greaterThan($productModel->expire_date)) {
    //                     // Rollback transaction and return error
    //                     DB::rollBack();
    //                     return response()->json([
    //                         'message' => "The product '{$productModel->name}' has expired (Expiration Date: {$productModel->expire_date->format('Y-m-d')}).",
    //                     ], 423); // HTTP 422 Unprocessable Entity
    //                 }

    //                 // Create sale item
    //                 SaleItem::create([
    //                     'sale_id' => $sale->id,
    //                     'product_id' => $product['id'],
    //                     'quantity' => $product['quantity'],
    //                     'unit_price' => $product['selling_price'],
    //                     'total_price' => $product['quantity'] * $product['selling_price'],
    //                 ]);

    //                 StockTransaction::create([
    //                     'product_id' => $product['id'],
    //                     'sale_id' => $sale->id,
    //                     'transaction_type' => 'Sold',
    //                     'quantity' => $product['quantity'],
    //                     'transaction_date' => now(),
    //                     'supplier_id' => $productModel->supplier_id ?? null,
    //                 ]);

    //                 // Update stock quantity
    //                 $productModel->update([
    //                     'stock_quantity' => $newStockQuantity,
    //                 ]);
    //             }
    //         }

    //         // Commit the transaction
    //         DB::commit();

    //         return response()->json(['message' => 'Sale recorded successfully!'], 201);
    //     } catch (\Exception $e) {
    //         // Rollback the transaction if any error occurs
    //         DB::rollBack();

    //         return response()->json([
    //             'message' => 'An error occurred while processing the sale.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }

    //     return response()->json([
    //         'message' => 'Customer details saved successfully!',
    //         'data' => $customer,
    //     ], 201);
    // }



public function submit(Request $request)
{


    if (!Gate::allows('hasRole', ['Admin', 'Cashier'])) {
        abort(403, 'Unauthorized');
    }

    $customer = null;
    $products = $request->input('products');

    $totalAmount = collect($products)->reduce(fn($carry, $product) => $carry + ($product['quantity'] * $product['selling_price']), 0);
    $totalCost = collect($products)->reduce(fn($carry, $product) => $carry + ($product['quantity'] * $product['cost_price']), 0);

    $productDiscounts = collect($products)->reduce(function ($carry, $product) {
        if (!empty($product['discount']) && $product['apply_discount']) {
            return $carry + (($product['selling_price'] - $product['discounted_price']) * $product['quantity']);
        }
        return $carry;
    }, 0);

    $couponDiscount = $request->input('appliedCoupon.discount') ?? 0;
    $totalDiscount = $productDiscounts + floatval($couponDiscount);

    DB::beginTransaction();

    try {
        // ✅ Create Customer if needed
        if (
            $request->filled('customer.contactNumber') ||
            $request->filled('customer.name') ||
            $request->filled('customer.email')
        ) {
            $phone = $request->input('customer.countryCode') . $request->input('customer.contactNumber');
            $email = $request->input('customer.email');
            $existingByEmail = Customer::where('email', $email)->first();
            $existingByPhone = Customer::where('phone', $phone)->first();

            if (!$existingByEmail && !$existingByPhone) {
                $customer = Customer::create([
                    'name' => $request->input('customer.name'),
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $request->input('customer.address', ''),
                    'member_since' => now()->toDateString(),
                    'loyalty_points' => 0,
                ]);
            } else {
                $customer = $existingByEmail ?? $existingByPhone;
            }
        }




        // ✅ Handle Cheque creation
        $cheque = null;


        if ($request->input('paymentMethod') === 'online') {


            $chequeData = $request->input('online');

            if (
                empty($chequeData['cheque_number']) ||
                empty($chequeData['bank_name']) ||
                empty($chequeData['cheque_date']) ||
                empty($chequeData['amount'])
            ) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Incomplete cheque details. Please provide cheque number, bank name, date, and amount.'
                ], 422);
            }

            $cheque = Cheque::create([
                'cheque_number' => $chequeData['cheque_number'],
                'bank_name'     => $chequeData['bank_name'],
                'cheque_date'   => $chequeData['cheque_date'],
                'amount'        => $chequeData['amount'],
                'notes'         => $chequeData['notes'] ?? null,
                'status'        => 'pending',
            ]);
        }



        // ✅ Create Sale
        $sale = Sale::create([


            'customer_id' => $customer?->id,
            'employee_id' => $request->input('employee_id'),
            'user_id'     => $request->input('userId'),
            'order_id'    => $request->input('orderid'),
            'total_amount' => $totalAmount,
            'discount'     => $totalDiscount,
            'total_cost'   => $totalCost,
            'payment_method' => $request->input('paymentMethod'),
            'sale_date'    => now()->toDateString(),
            'cash'         => $request->input('cash'),
            'is_whole'     => $request->boolean('isWholesale'),
            'custom_discount' => $request->input('custom_discount'),
            'guide_name' => $request->input('guide_name'),
            'guide_comi' => $request->input('guide_comi'),
            'guide_cash' => $request->input('guide_cash'),
            'guide_pending' => $request->boolean('guide_pending'),
            'total_to_include_guide' => $request->input('total_to_include_guide'),
            'credit_bill' => $request->boolean('credit_bill'),
              'cheque_id'   => $cheque?->id,
        ]);

        // ✅ Create Sale Items & update stock
        foreach ($products as $product) {
            $productModel = Product::find($product['id']);
            if (!$productModel) continue;

            $newStock = $productModel->stock_quantity - $product['quantity'];
            if ($newStock < 0) {
                DB::rollBack();
                return response()->json([
                    'message' => "Insufficient stock for {$productModel->name}. Available: {$productModel->stock_quantity}"
                ], 423);
            }

            if ($productModel->expire_date && now()->greaterThan($productModel->expire_date)) {
                DB::rollBack();
                return response()->json([
                    'message' => "Product '{$productModel->name}' expired on {$productModel->expire_date->format('Y-m-d')}."
                ], 423);
            }

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['selling_price'],
                'total_price' => $product['quantity'] * $product['selling_price'],
            ]);

            StockTransaction::create([
                'product_id' => $product['id'],
                'sale_id' => $sale->id,
                'transaction_type' => 'Sold',
                'quantity' => $product['quantity'],
                'transaction_date' => now(),
                'supplier_id' => $productModel->supplier_id ?? null,
            ]);

            $productModel->update([
                'stock_quantity' => $newStock,
            ]);
        }

        DB::commit();
        return response()->json(['message' => 'Sale recorded successfully!'], 201);

    } catch (\Exception $e) {

        DB::rollBack();
        return response()->json([
            'message' => 'An error occurred while processing the sale.',
            'error' => $e->getMessage(),
        ], 500);
    }
}










    public function updateOrder(Request $request, $order_id)
{
    try {
        $sale = Sale::where('order_id', $order_id)->firstOrFail();
        $isReturnBill = $request->input('returnBill', false);

        // Process existing items before updating
        $existingItems = SaleItem::where('sale_id', $sale->id)->get();

        // Only restore stock if not processing a return
        if (!$isReturnBill) {
            foreach ($existingItems as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }
            // Delete existing items to re-create them
            SaleItem::where('sale_id', $sale->id)->delete();
        }

        // Update the main sale record
        $sale->update([
            'customer_id' => $request->input('customer_id'),
            'employee_id' => $request->input('employee_id'),
            'payment_method' => $request->input('paymentMethod'),
            'cash' => $request->input('cash'),
            'custom_discount' => $request->input('custom_discount'),
            'status' => $request->input('status', $sale->status),
            'kitchen_note' => $request->input('kitchen_note'),
            'is_return_bill' => $isReturnBill ? 1 : $sale->is_return_bill,
            'return_date' => $isReturnBill ? now() : $sale->return_date,
        ]);

        // Process all products
        foreach ($request->input('products') as $product) {
            $productModel = Product::find($product['id']);

            if (!$productModel) {
                if ($isReturnBill) continue;

                $productModel = Product::create([
                    'name' => $product['name'],
                    'stock_quantity' => $product['quantity'],
                    'selling_price' => $product['selling_price'],
                    'supplier_id' => $product['supplier_id'] ?? null,
                ]);

                StockTransaction::create([
                    'product_id' => $productModel->id,
                    'sale_id' => $sale->id,
                    'transaction_type' => 'New Product',
                    'quantity' => $product['quantity'],
                    'transaction_date' => now(),
                    'supplier_id' => $product['supplier_id'] ?? null,
                ]);
            }

            $reasonId = $product['returnReason'] ?? null;
            $oldStockQuantity = $productModel->stock_quantity;

            if ($isReturnBill) {
                // Check if this product already exists in the sale
                $saleItem = SaleItem::where('sale_id', $sale->id)
                    ->where('product_id', $productModel->id)
                    ->first();

                if ($saleItem) {
                    // Handle quantity changes for returned items
                    $originalQuantity = $saleItem->getOriginal('quantity');
                    $newQuantity = $product['quantity'];
                    $quantityDifference = $originalQuantity - $newQuantity;

                    // Update sale item
                    $saleItem->update([
                        'reason_id' => $reasonId,
                        'quantity' => $newQuantity,
                        'total_price' => $newQuantity * $saleItem->unit_price,
                    ]);

                    // Handle stock adjustments based on return reason
                    if ($reasonId == "2") { // "Returning a New Product"
                        if ($quantityDifference > 0) {
                            // Reduced quantity - add back to stock
                            $productModel->increment('stock_quantity', $quantityDifference);

                            StockTransaction::create([
                                'product_id' => $productModel->id,
                                'sale_id' => $sale->id,
                                'transaction_type' => 'Return',
                                'quantity' => $quantityDifference,
                                'transaction_date' => now(),
                                'supplier_id' => $productModel->supplier_id ?? null,
                            ]);
                        } elseif ($quantityDifference < 0) {
                            // Increased quantity - deduct from stock
                            $productModel->decrement('stock_quantity', abs($quantityDifference));

                            StockTransaction::create([
                                'product_id' => $productModel->id,
                                'sale_id' => $sale->id,
                                'transaction_type' => 'Exchange',
                                'quantity' => abs($quantityDifference),
                                'transaction_date' => now(),
                                'supplier_id' => $productModel->supplier_id ?? null,
                            ]);
                        }
                    } elseif ($reasonId != "1") { // Not damaged product
                        // Standard return logic for other reasons
                        if ($quantityDifference > 0) {
                            $productModel->increment('stock_quantity', $quantityDifference);

                            StockTransaction::create([
                                'product_id' => $productModel->id,
                                'sale_id' => $sale->id,
                                'transaction_type' => 'Return',
                                'quantity' => $quantityDifference,
                                'transaction_date' => now(),
                                'supplier_id' => $productModel->supplier_id ?? null,
                            ]);
                        }
                    }
                } else {
                    // New item in return bill - create sale item
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $productModel->id,
                        'quantity' => $product['quantity'],
                        'unit_price' => $product['selling_price'],
                        'total_price' => $product['quantity'] * $product['selling_price'],
                        'reason_id' => $reasonId,
                    ]);

                    // For "Returning a New Product", deduct from stock for new items
                    if ($reasonId == "2") {
                        $productModel->decrement('stock_quantity', $product['quantity']);

                        StockTransaction::create([
                            'product_id' => $productModel->id,
                            'sale_id' => $sale->id,
                            'transaction_type' => 'Exchange',
                            'quantity' => $product['quantity'],
                            'transaction_date' => now(),
                            'supplier_id' => $productModel->supplier_id ?? null,
                        ]);
                    }
                }
            } else {
                // Standard update process
                if ($productModel->stock_quantity < $product['quantity']) {
                    return response()->json([
                        'message' => "Insufficient stock for product: {$productModel->name} ({$productModel->stock_quantity} available)",
                    ], 423);
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $productModel->id,
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['selling_price'],
                    'total_price' => $product['quantity'] * $product['selling_price'],
                ]);

                $productModel->decrement('stock_quantity', $product['quantity']);

                StockTransaction::create([
                    'product_id' => $productModel->id,
                    'sale_id' => $sale->id,
                    'transaction_type' => 'Sold',
                    'quantity' => $product['quantity'],
                    'transaction_date' => now(),
                    'supplier_id' => $productModel->supplier_id ?? null,
                ]);
            }
        }

        return response()->json(['message' => $isReturnBill ? 'Return processed successfully!' : 'Order updated successfully!'], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Order not found'], 404);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function getPastOrder(Request $request)
{
    $request->validate([
        'barcode' => 'required|string',
    ]);

    try {
        $order = Sale::where('order_id', $request->barcode)
            ->with(['saleItems.product', 'saleItems.returnReason', 'employee', 'customer'])
            ->first();

        \Log::info('Raw Order Data:', $order ? $order->toArray() : ['order' => null]);

        if (!$order) {
            return response()->json([
                'error' => 'Order not found'
            ], 404);
        }

        // Transform the data to match frontend expectations
        $transformedOrder = [
            'order_id' => $order->order_id,
            'products' => $order->saleItems->map(function ($item) {
                return [
                    ...$item->product->toArray(),
                    'pivot' => [
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'reason_id' => $item->reason_id,
                        'return_reason' => $item->returnReason ? [
                            'id' => $item->returnReason->id,
                            'reason' => $item->returnReason->reason
                        ] : null
                    ]
                ];
            }),
            'customer_name' => $order->customer->name ?? '',
            'customer_phone' => $order->customer->contactNumber ?? '',
            'customer_email' => $order->customer->email ?? '',
            'return_date' => $order->return_date,
            'employee_id' => $order->employee_id,
            'payment_method' => $order->payment_method,
            'cash' => $order->cash,
            'custom_discount' => $order->custom_discount,
            'discount' => $order->discount,
            'is_return_bill' => $order->is_return_bill,
        ];

        return response()->json([
            'order' => $transformedOrder
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error fetching order details: ' . $e->getMessage()
        ], 500);
    }
}

    public function markGuideCompleted(Sale $sale)
    {
        $sale->update([
            'guide_pending' => 0,
        ]);

        return response()->json(['message' => 'Guide marked as completed.']);
    }

}
