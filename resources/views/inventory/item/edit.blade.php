@extends('layouts.inventory.app')
@section('title', 'Inventory | Items')
@section('navbar')
    @include('layouts.inventory.navbar')
@endsection
@section('content')
<div class="container">
    <h2>Edit Inventory Item</h2>
    <form action="{{ route('inventory.update', $inventoryItem->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Inventory Number</label>
            <input type="text" class="form-control" name="inventory_number" value="{{ $inventoryItem->inventory_number }}" required>
        </div>

        <div class="mb-3">
            <label>Item Name</label>
            <input type="text" class="form-control" name="name" value="{{ $inventoryItem->name }}" required>
        </div>

        <div class="mb-3">
            <label>Condition</label>
            <input type="text" class="form-control" name="condition" value="{{ $inventoryItem->condition }}" required>
        </div>

        <div class="mb-3">
            <label>Quantity</label>
            <input type="number" class="form-control" name="quantity" value="{{ $inventoryItem->quantity }}" required>
        </div>

        <div class="mb-3">
            <label>Unit Price (Rp)</label>
            <input type="number" class="form-control" name="unit_price" value="{{ $inventoryItem->unit_price }}" required>
        </div>

        <div class="mb-3">
            <label>Book Value (Rp)</label>
            <input type="number" class="form-control" name="book_value" value="{{ $inventoryItem->book_value }}" required>
        </div>

        <div class="mb-3">
            <label>Year of Purchase</label>
            <input type="number" class="form-control" name="purchase_year" value="{{ $inventoryItem->purchase_year }}" required>
        </div>

        <div class="mb-3">
            <label>Note</label>
            <textarea class="form-control" name="note">{{ $inventoryItem->note }}</textarea>
        </div>

        <div class="mb-3">
            <label>Initial Depreciation (Rp)</label>
            <input type="number" class="form-control" name="initial_depreciation" value="{{ $inventoryItem->initial_depreciation }}">
        </div>

        <div class="mb-3">
            <label>Annual Depreciation (Rp)</label>
            <input type="number" class="form-control" name="annual_depreciation" value="{{ $inventoryItem->annual_depreciation }}">
        </div>

        <div class="mb-3">
            <label>Accumulated Depreciation (Rp)</label>
            <input type="number" class="form-control" name="accumulated_depreciation" value="{{ $inventoryItem->accumulated_depreciation }}">
        </div>

        <div class="mb-3">
            <label>Residual Value (Rp)</label>
            <input type="number" class="form-control" name="residual_value" value="{{ $inventoryItem->residual_value }}">
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection