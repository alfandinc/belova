@extends('layouts.inventory.app')
@section('title', 'Inventory | Items')
@section('navbar')
    @include('layouts.inventory.navbar')
@endsection

@section('content')
<div class="container">
    <h1>Inventory List</h1>
    <a href="{{ route('inventory.item.create') }}" class="btn btn-primary mb-3">+ Add Item</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>NO INV</th>
                <th>Name</th>
                <th>Condition</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Book Value</th>
                <th>Year</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
            <tr>
                <td>{{ $item->inventory_number }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->condition }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rp {{ number_format($item->unit_price) }}</td>
                <td>Rp {{ number_format($item->book_value) }}</td>
                <td>{{ $item->purchase_year }}</td>
                <td>
                    <a href="{{ route('inventory.item.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('inventory.item.destroy', $item->id) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
