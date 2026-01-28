<?php

namespace App\Http\Controllers\Backend\Pos;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\PosCart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;


class CartController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $cartItems = PosCart::where('user_id', auth()->id())
                ->with('product')
                ->latest('created_at')
                ->get()
                ->map(function ($item) {
                    // Calculate row total for each item
                    // Hybrid Precision: Round item totals to nearest cent
                    $item->row_total = round($item->quantity * $item->product->discounted_price, 2);
                    return $item;
                });
            $total = $cartItems->sum('row_total');
            return response()->json([
                'carts' => $cartItems,
                'total' => round($total, 2)
            ]);
        }
        
        // On fresh page load, we don't clear the cart anymore. 
        // We let the frontend detect if it should continue or offer restore from journal.
        return view('backend.cart.index');
    }


    public function checkJournal()
    {
        $path = storage_path('app/current_sale.journal');
        if (File::exists($path)) {
            $journal = json_decode(File::get($path), true);
            return response()->json([
                'exists' => true,
                'data' => $journal
            ]);
        }
        return response()->json(['exists' => false]);
    }

    public function getProducts(Request $request)
    {
        $search = $request->search;
        $barcode = $request->barcode;
        $page = $request->get('page', 1);
        
        // Cache product list for browse mode (no search/barcode) - 60 seconds
        // This significantly reduces database load for common POS usage
        $cacheKey = "pos_products_page_{$page}";
        $useCache = empty($search) && empty($barcode);
        
        if ($useCache && Cache::has($cacheKey)) {
            $products = Cache::get($cacheKey);
        } else {
            $products = Product::query()
                ->select(['id', 'name', 'image', 'price', 'discount', 'discount_type', 'quantity', 'sku', 'status'])
                ->active();


            // Search by name if provided
            $products->when($search, function ($query, $search) {
                $query->where('name', 'LIKE', "%{$search}%");
            });

            // Search by barcode if provided
            $products->when($barcode, function ($query, $barcode) {
                $query->where('sku', $barcode);
            });

            // Reduced pagination for faster initial load (was 96)
            $products = $products->orderBy('name', 'asc')->paginate(24);
            
            // Cache only browse mode results
            if ($useCache) {
                Cache::put($cacheKey, $products, 60);
            }
        }

        if (request()->wantsJson()) {
            return ProductResource::collection($products);
        }
    }

    public function store(Request $request)
    {
        // Validate request input
        $request->validate([
            'id' => 'required|exists:products,id',
        ]);

        $product_id = $request->id;

        // Fetch the product
        $product = Product::find($product_id);

        // Check if the product is active and has sufficient stock
        if (!$product->status) {
            return response()->json(['message' => 'Product is not available'], 400);
        }

        if ($product->quantity <= 0) {
            return response()->json(['message' => 'Insufficient stock available'], 400);
        }

        // Fetch the cart item for the current user and product
        $cartItem = PosCart::where('user_id', auth()->id())->where('product_id', $product_id)->first();

        if ($cartItem) {
            // If the product is already in the cart, increment the quantity
            if ($cartItem->quantity < $product->quantity) {
                $cartItem->quantity += 1;
                $cartItem->save();
                $cartItem->load('product');
                $this->syncJournal();
                return response()->json(['message' => 'Quantity updated', 'cart' => $cartItem], 200);

            } else {
                return response()->json(['message' => 'Cannot add more, stock limit reached'], 400);
            }
        } else {
            // If not in the cart, create a new cart item
            $cart = new PosCart();
            $cart->user_id = auth()->id();
            $cart->product_id = $product_id;
            $cart->quantity = 1;
            $cart->save();
            $cart->load('product');
            $this->syncJournal();
            return response()->json(['message' => 'Product added to cart', 'cart' => $cart], 201);
        }
    }

    private function syncJournal()
    {
        try {
            $userId = auth()->id();
            $cartItems = PosCart::where('user_id', $userId)
                ->with('product')
                ->get()
                ->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'name' => $item->product->name,
                        'price' => $item->product->discounted_price,
                        'quantity' => $item->quantity,
                    ];
                });

            $path = storage_path('app/current_sale.journal');
            file_put_contents($path, json_encode([
                'user_id' => $userId,
                'items' => $cartItems,
                'timestamp' => now()->toDateTimeString()
            ], JSON_PRETTY_PRINT), LOCK_EX);
        } catch (\Exception $e) {
            \Log::error("Journal Sync Failed: " . $e->getMessage());
        }
    }


    public function increment(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id'
        ]);

        $cart = PosCart::with('product')->findOrFail($request->id);
        if ($cart->product->quantity <= 0) {
            return response()->json(['message' => 'Insufficient stock available'], 400);
        }
        if ($cart->quantity == $cart->product->quantity) {
            return response()->json(['message' => 'Cannot add more, stock limit reached'], 400);
        }
        $cart->quantity = $cart->quantity + 1;
        $cart->save();
        $this->syncJournal();
        return response()->json(['message' => 'Cart Updated successfully'], 200);

    }
    public function decrement(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id'
        ]);
        $cart = PosCart::findOrFail($request->id);
        if ($cart->quantity <= 1) {
            return response()->json(['message' => 'Quantity cannot be less than 1.'], 400);
        }
        $cart->quantity = $cart->quantity - 1;
        $cart->save();
        $this->syncJournal();
        return response()->json(['message' => 'Cart Updated successfully'], 200);

    }
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id'
        ]);

        $cart = PosCart::findOrFail($request->id);
        $cart->delete();
        $this->syncJournal();

        return response()->json(['message' => 'Item successfully deleted'], 200);

    }
    public function empty()
    {
        $deletedCount = PosCart::where('user_id', auth()->id())->delete();

        if ($deletedCount > 0) {
            $this->syncJournal();
            return response()->json(['message' => 'Cart successfully cleared'], 200);
        }


        return response()->json(['message' => 'Cart is already empty'], 204);
    }
    
    /**
     * Direct quantity update for weight-based selling
     * Allows decimal values like 0.5 for 500g, 0.714 for 714g
     */
    public function updateQuantity(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id',
            'quantity' => 'required|numeric|min:0.001|max:9999'
        ]);

        $cart = PosCart::with('product')->findOrFail($request->id);
        $newQuantity = (float) $request->quantity;
        
        // --- SECURITY: Bounds check ---
        if ($newQuantity <= 0) {
            return response()->json(['message' => 'Quantity must be greater than zero'], 400);
        }

        $newQuantity = round($newQuantity, 3);
        
        // Check stock availability
        if ($newQuantity > $cart->product->quantity) {
            return response()->json([
                'message' => 'Insufficient stock. Available: ' . $cart->product->quantity
            ], 400);
        }

        $cart->quantity = $newQuantity;
        $cart->save();
        $this->syncJournal();

        return response()->json(['message' => 'Quantity updated to ' . $newQuantity], 200);

    }

    /**
     * Update cart item by desired price (auto-calculate quantity)
     * Customer says "100 rupees ka dedo" → System calculates quantity
     * Example: Rs.100 ÷ Rs.1400/kg = 0.071 kg (71 grams)
     */
    public function updateByPrice(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id',
            'price' => 'required|numeric|min:1|max:9999999'
        ]);

        $cart = PosCart::with('product')->findOrFail($request->id);
        $desiredPrice = (float) $request->price;
        $pricePerUnit = (float) $cart->product->discounted_price;

        // Prevent division by zero
        if ($pricePerUnit <= 0) {
            return response()->json(['message' => 'Invalid product price'], 400);
        }

        // Calculate quantity: desired_price / price_per_unit
        // Round UP to nearest 0.001 so total is always >= entered amount
        $rawQuantity = $desiredPrice / $pricePerUnit;
        $calculatedQuantity = ceil($rawQuantity * 1000) / 1000; // Round up to 3 decimals

        // Minimum quantity check (at least 1 gram = 0.001 kg)
        if ($calculatedQuantity < 0.001) {
            return response()->json([
                'message' => 'Amount too small. Minimum: Rs.' . ceil($pricePerUnit * 0.001)
            ], 400);
        }

        // Stock availability check
        if ($calculatedQuantity > $cart->product->quantity) {
            $maxPrice = round($cart->product->quantity * $pricePerUnit, 2);
            return response()->json([
                'message' => 'Insufficient stock. Max available: Rs.' . $maxPrice
            ], 400);
        }

        $cart->quantity = $calculatedQuantity;
        $cart->save();
        $this->syncJournal();


        // Return both new quantity and calculated total for UI update
        $newTotal = round($calculatedQuantity * $pricePerUnit, 2);
        return response()->json([
            'message' => 'Updated: ' . $calculatedQuantity . ' kg = Rs.' . $newTotal,
            'quantity' => $calculatedQuantity,
            'total' => $newTotal
        ], 200);
    }
}
