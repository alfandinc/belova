@extends('layouts.inventory.app')
@section('title', 'Inventory | Items')
@section('navbar')
    @include('layouts.inventory.navbar')
@endsection

@section('content')
<div class="container py-4">
    <h3 class="mb-4"><i class="fas fa-plus-circle"></i> Add New Inventory Item</h3>

    <form action="{{ route('inventory.item.store') }}" method="POST">
        @csrf
        <div class="form-row">
            <div class="form-group col-md-6">
                <label >Inventory Number</label>
                <input type="text" name="inventory_number" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label >Item Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label >Condition</label>
                <select name="condition" class="form-control" required>
                    <option value="">-- Select Condition --</option>
                    <option value="New">New</option>
                    <option value="Good">Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                </select>
            </div>
            <div class="form-group col-md-6">
        <label >Quantity</label>
        <input type="number" name="quantity" class="form-control" id="quantity" min="1" required>
    </div>
        </div>

        <div class="form-row">
    <div class="form-group col-md-6">
        <label >Unit Price</label>
        <input type="number" name="unit_price" class="form-control" id="unit_price" step="0.01" min="0" required>
    </div>
    <div class="form-group col-md-6">
        <label >Purchase Year</label>
        <select name="purchase_year" class="form-control" id="purchase_year" required>
            <option value="">-- Select Year --</option>
            @for ($year = date('Y'); $year >= 2005; $year--)
                <option value="{{ $year }}">{{ $year }}</option>
            @endfor
        </select>
    </div>
</div>

        <div class="form-group">
            <label >Note</label>
            <textarea name="note" class="form-control" rows="2"></textarea>
        </div>

        <hr class="bg-secondary">

        <h5 ><i class="fas fa-chart-line"></i> Depreciation Summary</h5>
        <div class="form-row">
    <div class="form-group col-md-3">
        <label >Book Value</label>
        <input type="text" class="form-control" id="book_value" disabled>
    </div>
    <div class="form-group col-md-3">
        <label >Annual Depreciation</label>
        <input type="text" class="form-control" id="annual_depreciation" disabled>
    </div>
    <div class="form-group col-md-3">
        <label >Accumulated Depreciation</label>
        <input type="text" class="form-control" id="accumulated_depreciation" disabled>
    </div>
    <div class="form-group col-md-3">
        <label >Residual Value</label>
        <input type="text" class="form-control" id="residual_value" disabled>
    </div>
</div>

        <button type="submit" class="btn btn-success mt-3">
            <i class="fas fa-save"></i> Save Item
        </button>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function calculateDepreciation() {
        const quantity = parseFloat(document.getElementById('quantity').value) || 0;
        const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
        const purchaseYear = parseInt(document.getElementById('purchase_year').value) || 0;
        const currentYear = new Date().getFullYear();
        const economicLifetime = 5;

        // Calculate depreciation values
        const bookValue = quantity * unitPrice;
        const annualDepreciation = unitPrice / economicLifetime;
        const yearsUsed = Math.max(0, currentYear - purchaseYear);
        const accumulatedDepreciation = Math.min(unitPrice, annualDepreciation * yearsUsed);
        const residualValue = Math.max(0, unitPrice - accumulatedDepreciation);

        // Set the calculated values in the form
        document.getElementById('book_value').value = bookValue.toFixed(2);
        document.getElementById('annual_depreciation').value = annualDepreciation.toFixed(2);
        document.getElementById('accumulated_depreciation').value = accumulatedDepreciation.toFixed(2);
        document.getElementById('residual_value').value = residualValue.toFixed(2);
    }

    // Attach event listeners to inputs
    document.getElementById('quantity').addEventListener('input', calculateDepreciation);
    document.getElementById('unit_price').addEventListener('input', calculateDepreciation);
    document.getElementById('purchase_year').addEventListener('change', calculateDepreciation);
</script>


@endsection