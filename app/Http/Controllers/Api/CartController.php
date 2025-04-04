<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Get cart items for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCartItems()
    {
        try {
            // Get authenticated user
            $user = Auth::user();
            
            // Get draft order
            $order = Order::where('userID', $user->userID)
                          ->where('order_status', 'draft')
                          ->first();

            if (!$order) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cart is empty',
                    'cart_items' => [],
                    'cart_count' => 0,
                    'cart_total' => 0
                ]);
            }

            // Get cart items with item details
            $cartItems = Cart::where('orderID', $order->orderID)
                             ->with(['item.poultry', 'item.user.company', 'item.location'])
                             ->get();

            // Calculate cart total
            $cartTotal = 0;
            foreach ($cartItems as $item) {
                $cartTotal += $item->price_at_purchase * $item->quantity;
            }

            // Get cart count
            $cartCount = $cartItems->sum('quantity');

            return response()->json([
                'success' => true,
                'cart_items' => $cartItems,
                'cart_count' => $cartCount,
                'cart_total' => $cartTotal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add an item to the cart
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToCart(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'itemID' => 'required|exists:items,itemID',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get authenticated user
            $user = Auth::user();

            // Get or create draft order
            $order = Order::where('userID', $user->userID)
                          ->where('order_status', 'draft')
                          ->first();

            if (!$order) {
                $order = Order::create([
                    'userID' => $user->userID,
                    'order_status' => 'draft'
                ]);
            }

            // Get item details
            $item = Item::findOrFail($request->itemID);
            
            // Check if item already exists in cart
            $cartItem = Cart::where('orderID', $order->orderID)
                            ->where('itemID', $request->itemID)
                            ->first();

            if ($cartItem) {
                // Update existing cart item
                $cartItem->quantity += $request->quantity;
                $cartItem->save();
            } else {
                // Create new cart item
                $cartItem = Cart::create([
                    'orderID' => $order->orderID,
                    'itemID' => $request->itemID,
                    'quantity' => $request->quantity,
                    'price_at_purchase' => $item->price
                ]);
            }

            // Get cart count for response
            $cartCount = Cart::where('orderID', $order->orderID)->sum('quantity');

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'cart_count' => $cartCount,
                'cart_item' => $cartItem
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCartItem(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'cartID' => 'required|exists:carts,cartID',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get authenticated user
            $user = Auth::user();

            // Get cart item
            $cartItem = Cart::findOrFail($request->cartID);
            
            // Verify ownership
            $order = Order::findOrFail($cartItem->orderID);
            if ($order->userID !== $user->userID || $order->order_status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to cart item'
                ], 403);
            }

            // Update quantity
            $cartItem->quantity = $request->quantity;
            $cartItem->save();

            // Get updated cart info
            $cartCount = Cart::where('orderID', $order->orderID)->sum('quantity');
            $cartTotal = Cart::where('orderID', $order->orderID)
                            ->get()
                            ->sum(function($item) {
                                return $item->price_at_purchase * $item->quantity;
                            });

            return response()->json([
                'success' => true,
                'message' => 'Cart item updated successfully',
                'cart_item' => $cartItem,
                'cart_count' => $cartCount,
                'cart_total' => $cartTotal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     *
     * @param  int  $cartID
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCartItem($cartID)
    {
        try {
            // Get authenticated user
            $user = Auth::user();

            // Get cart item
            $cartItem = Cart::findOrFail($cartID);
            
            // Verify ownership
            $order = Order::findOrFail($cartItem->orderID);
            if ($order->userID !== $user->userID || $order->order_status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to cart item'
                ], 403);
            }

            // Store orderID for later use
            $orderID = $cartItem->orderID;

            // Delete cart item
            $cartItem->delete();

            // Check if there are any remaining cart items for this order
            $remainingItems = Cart::where('orderID', $orderID)->count();
            
            // If no items remain, delete the order
            if ($remainingItems === 0) {
                Order::where('orderID', $orderID)
                     ->where('order_status', 'draft')
                     ->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Item removed and cart cleared successfully',
                    'cart_count' => 0,
                    'cart_total' => 0
                ]);
            }

            // Get updated cart info
            $cartCount = Cart::where('orderID', $orderID)->sum('quantity');
            $cartTotal = Cart::where('orderID', $orderID)
                            ->get()
                            ->sum(function($item) {
                                return $item->price_at_purchase * $item->quantity;
                            });

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully',
                'cart_count' => $cartCount,
                'cart_total' => $cartTotal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear the entire cart
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCart()
    {
        try {
            // Get authenticated user
            $user = Auth::user();

            // Get draft order
            $order = Order::where('userID', $user->userID)
                          ->where('order_status', 'draft')
                          ->first();

            if (!$order) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cart is already empty'
                ]);
            }

            // Delete all cart items
            Cart::where('orderID', $order->orderID)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}