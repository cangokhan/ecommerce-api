<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'] ?? 0,
            'status' => $validated['status'] ?? 'active',
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('creator'),
        ], 201);
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::with('creator')
            ->latest()
            ->paginate(15);

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'product' => $product->load('creator'),
        ]);
    }
}

