<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::all();
        return view('inventory.item.index', compact('items'));
    }

    public function create()
    {
        return view('inventory.item.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'inventory_number' => 'required|string',
            'name' => 'required|string',
            'condition' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'purchase_year' => 'required|integer|min:1900|max:' . date('Y'),
            
        ]);

        $economicLifetime = 5; // years, adjust as needed
        $currentYear = date('Y');

        $quantity = $request->quantity;
        $unitPrice = $request->unit_price;
        $purchaseYear = $request->purchase_year;

        $bookValue = $quantity * $unitPrice;
        $annualDepreciation = $unitPrice / $economicLifetime;
        $yearsUsed = max(0, $currentYear - $purchaseYear);
        $accumulatedDepreciation = min($unitPrice, $annualDepreciation * $yearsUsed);
        $residualValue = max(0, $unitPrice - $accumulatedDepreciation);

        Item::create([
            'inventory_number' => $request->inventory_number,
            'name' => $request->name,
            'condition' => $request->condition,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'book_value' => $bookValue,
            'purchase_year' => $purchaseYear,
            'note' => $request->note,

            'initial_depreciation' => 0,
            'annual_depreciation' => $annualDepreciation,
            'accumulated_depreciation' => $accumulatedDepreciation,
            'residual_value' => $residualValue,
        ]);

        return redirect()->route('inventory.item.index')->with('success', 'Item added successfully!');
    }


    public function show(Item $item)
    {
        return view('inventory.item.show', compact('item'));
    }

    public function edit(Item $item)
    {
        return view('inventory.item.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'condition' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'purchase_year' => 'required|integer|min:1900|max:' . date('Y'),
            // other validations...
        ]);

        $economicLifetime = 5;
        $currentYear = date('Y');

        $quantity = $request->quantity;
        $unitPrice = $request->unit_price;
        $purchaseYear = $request->purchase_year;

        $bookValue = $quantity * $unitPrice;
        $annualDepreciation = $unitPrice / $economicLifetime;
        $yearsUsed = max(0, $currentYear - $purchaseYear);
        $accumulatedDepreciation = min($unitPrice, $annualDepreciation * $yearsUsed);
        $residualValue = max(0, $unitPrice - $accumulatedDepreciation);

        $item->update([
            'name' => $request->name,
            'condition' => $request->condition,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'book_value' => $bookValue,
            'purchase_year' => $purchaseYear,
            'note' => $request->note,

            'initial_depreciation' => 0,
            'annual_depreciation' => $annualDepreciation,
            'accumulated_depreciation' => $accumulatedDepreciation,
            'residual_value' => $residualValue,
        ]);

        return redirect()->route('inventory.item.index')->with('success', 'Item updated successfully!');
    }


    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()->route('inventory.item.index')->with('success', 'Item deleted!');
    }
}
