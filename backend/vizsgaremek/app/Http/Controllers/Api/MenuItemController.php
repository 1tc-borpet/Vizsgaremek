<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $items = MenuItem::with('category')->get();
        return response()->json($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:menu_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|url',
            'preparation_time' => 'nullable|integer',
            'is_available' => 'nullable|boolean',
            'order' => 'nullable|integer',
        ]);

        $item = MenuItem::create($validated);
        return response()->json($item, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = MenuItem::with('category')->findOrFail($id);
        return response()->json($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = MenuItem::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'image_url' => 'nullable|url',
            'preparation_time' => 'nullable|integer',
            'is_available' => 'nullable|boolean',
            'order' => 'nullable|integer',
        ]);

        $item->update($validated);
        return response()->json($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        MenuItem::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    /**
     * Get items by category
     */
    public function byCategory(string $categoryId)
    {
        $items = MenuItem::where('category_id', $categoryId)
            ->where('is_available', true)
            ->orderBy('order')
            ->get();
        return response()->json($items);
    }
}

