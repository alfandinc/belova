@extends('layouts.hrd.app')
@section('title', 'HRD | Tambah Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Performance Evaluation Periods</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('hrd.performance.periods.create') }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Create New Period
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $period)
                    <tr>
                        <td>{{ $period->name }}</td>
                        <td>{{ $period->start_date->format('d M Y') }}</td>
                        <td>{{ $period->end_date->format('d M Y') }}</td>
                        <td>
                            <span class="badge badge-{{ $period->status == 'pending' ? 'warning' : ($period->status == 'active' ? 'primary' : 'success') }}">
                                {{ ucfirst($period->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('hrd.performance.periods.show', $period) }}" class="btn btn-sm btn-info">
                                <i class="fa fa-eye"></i> Details
                            </a>
                            @if($period->status == 'pending')
                            <a href="{{ route('hrd.performance.periods.edit', $period) }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <form action="{{ route('hrd.performance.periods.destroy', $period) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this period?')">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">No evaluation periods found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="mt-4">
                {{ $periods->links() }}
            </div>
        </div>
    </div>
</div>
@endsection