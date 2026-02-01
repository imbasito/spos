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
                    // Use price_override if set, otherwise fallback to product's discounted price
                    $effectivePrice = $item->price_override ?? $item->product->discounted_price;
                    $item->row_total = $item->row_total_override ?? round($item->quantity * $effectivePrice, 2);
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
        // DEBUG: Log all incoming data to diagnose "id is required" issue
        \Log::info('CartController@store called', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'all_input' => $request->all(),
            'raw_content' => $request->getContent()
        ]);
        
        // Get the raw input values
        $inputId = $request->input('id');
        $inputBarcode = $request->input('barcode');
        
        // Clean and validate - be flexible with input types
        $cleanId = null;
        $cleanBarcode = null;
        
        if ($inputId !== null && $inputId !== '') {
            $cleanId = is_numeric($inputId) ? (int)$inputId : null;
        }
        
        if ($inputBarcode !== null && $inputBarcode !== '') {
            $cleanBarcode = trim((string)$inputBarcode);
            if (strlen($cleanBarcode) === 0) {
                $cleanBarcode = null;
            }
        }
        
        // Must have at least one valid identifier
        if ($cleanId === null && $cleanBarcode === null) {
            return response()->json([
                'message' => 'Please scan a barcode or select a product'
            ], 422);
        }

        // Find the product
        $product = null;
        
        if ($cleanBarcode) {
            // Check both SKU and Barcode columns for maximum professional compatibility
            $product = Product::where('sku', $cleanBarcode)
                             ->orWhere('barcode', $cleanBarcode)
                             ->first();
                             
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found for barcode: ' . $cleanBarcode
                ], 404);
            }
        } else {
            $product = Product::find($cleanId);
            
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found with ID: ' . $cleanId
                ], 404);
            }
        }

        $product_id = $product->id;

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
                        'price' => $item->price_override ?? $item->product->discounted_price,
                        'quantity' => $item->quantity,
                        'price_override' => $item->price_override,
                        'row_total_override' => $item->row_total_override,
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

    /**
     * Delete the journal file
     * Called after successful checkout, restore, or discard
     */
    public function deleteJournal()
    {
        try {
            $path = storage_path('app/current_sale.journal');
            if (File::exists($path)) {
                File::delete($path);
                \Log::info('Journal deleted successfully');
                return response()->json(['message' => 'Journal deleted'], 200);
            }
            return response()->json(['message' => 'Journal does not exist'], 204);
        } catch (\Exception $e) {
            \Log::error('Journal deletion failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete journal'], 500);
        }
    }


    public function increment(Request $request)
    {
        // DEBUG: Log incoming data
        \Log::info('CartController@increment called', ['input' => $request->all()]);
        
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id'
        ]);

        $cart = PosCart::with('product')->findOrFail($request->id);
        if ($cart->product->quantity <= 0) {
            return response()->json(['message' => 'Insufficient stock available'], 400);
        }
        if ($cart->quantity >= $cart->product->quantity) {
            return response()->json(['message' => 'Cannot add more, stock limit reached'], 400);
        }
        $cart->quantity = $cart->quantity + 1;
        // Clear manual amount override when quantity changes
        $cart->row_total_override = null;
        $cart->save();
        $this->syncJournal();
        return response()->json(['message' => 'Cart Updated successfully'], 200);

    }
    public function decrement(Request $request)
    {
        // DEBUG: Log incoming data
        \Log::info('CartController@decrement called', ['input' => $request->all()]);
        
        $request->validate([
            'id' => 'required|integer|exists:pos_carts,id'
        ]);
        $cart = PosCart::findOrFail($request->id);
        if ($cart->quantity <= 1) {
            return response()->json(['message' => 'Quantity cannot be less than 1.'], 400);
        }
        $cart->quantity = $cart->quantity - 1;
        // Clear manual amount override when quantity changes
        $cart->row_total_override = null;
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
            // Delete journal file when cart is emptied (discard scenario)
            $path = storage_path('app/current_sale.journal');
            if (File::exists($path)) {
                File::delete($path);
            }
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
        try {
            \Log::info('updateQuantity called', [
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            $request->validate([
                'id' => 'required|integer|exists:pos_carts,id',
                'quantity' => 'required|numeric|min:0.001|max:9999'
            ]);

            // IMPORTANT: Check ownership - ensure cart belongs to current user
            $cart = PosCart::where('id', $request->id)
                ->where('user_id', auth()->id())
                ->with('product')
                ->firstOrFail();
            
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
            // IMPORTANT: Clear row_total_override when quantity changes
            // This ensures the total is recalculated based on new quantity
            $cart->row_total_override = null;
            $cart->save();
            $this->syncJournal();

            $effectivePrice = $cart->price_override ?? $cart->product->discounted_price;
            $newTotal = round($newQuantity * $effectivePrice, 2);

            return response()->json([
                'message' => 'Quantity updated to ' . $newQuantity,
                'total' => $newTotal
            ], 200);
        } catch (\Exception $e) {
            \Log::error('updateQuantity error', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update cart item by desired amount (auto-calculate quantity)
     * Customer says "100 rupees ka dedo" → System calculates quantity
     * Example: Rs.100 ÷ Rs.50/kg = 2kg
     * 
     * PROFESSIONAL: This updates BOTH row_total AND quantity
     */
    public function updateByPrice(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:pos_carts,id',
                'price' => 'required|numeric|min:0', 
            ]);

            $cart = PosCart::where('user_id', auth()->id())->where('id', $request->id)->firstOrFail();
            
            $targetAmount = $request->price;
            
            // Get effective rate (discounted_price after discount applied)
            $effectiveRate = $cart->price_override ?? $cart->product->discounted_price;
            
            if ($effectiveRate <= 0) {
                return response()->json(['message' => 'Cannot calculate quantity - invalid rate'], 400);
            }
            
            // Calculate new quantity: Quantity = Amount ÷ Effective Rate
            $newQuantity = round($targetAmount / $effectiveRate, 3);
            
            // Update both quantity and row total
            $cart->quantity = $newQuantity;
            $cart->row_total_override = $targetAmount;
            
            $cart->save();
            $this->syncJournal();

            return response()->json([
                'success' => true,
                'quantity' => $newQuantity,
                'amount' => $targetAmount
            ]);
        } catch (\Exception $e) {
            \Log::error('Cart updateByPrice error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update price override (Rate)
     * When rate changes, recalculate row_total = quantity × new rate
     */
    public function updateRate(Request $request)
    {
        try {
            \Log::info('updateRate called', [
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            $request->validate([
                'id' => 'required|integer|exists:pos_carts,id',
                'price' => 'required|numeric|min:0|max:9999999'
            ]);

            // IMPORTANT: Check ownership - ensure cart belongs to current user
            $cart = PosCart::where('id', $request->id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            
            $newRate = (float) $request->price;
            
            // Set the price override (new rate)
            $cart->price_override = $newRate;
            
            // Clear row_total_override to force recalculation
            // New Amount = Quantity × New Rate
            $cart->row_total_override = null;
            
            $cart->save();
            $this->syncJournal();
            
            $newAmount = round($cart->quantity * $newRate, 2);

            return response()->json([
                'message' => 'Rate updated successfully',
                'new_amount' => $newAmount
            ], 200);
        } catch (\Exception $e) {
            \Log::error('updateRate error', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
