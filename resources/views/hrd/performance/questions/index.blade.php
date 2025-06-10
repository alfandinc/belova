@extends('layouts.hrd.app')
@section('title', 'HRD | Tambah Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Performance Evaluation Questions</h2>
        </div>
        <div class="col-md-4 text-right">
            <div class="btn-group">
                <a href="{{ route('hrd.performance.questions.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> New Question
                </a>
                <a href="{{ route('hrd.performance.categories.create') }}" class="btn btn-secondary">
                    <i class="fa fa-plus"></i> New Category
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @forelse($categories as $category)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $category->name }}</h5>
                <div class="btn-group">
                    <a href="{{ route('hrd.performance.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-edit"></i> Edit Category
                    </a>
                    <form action="{{ route('hrd.performance.categories.destroy', $category) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this category?')">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if($category->description)
                    <p class="text-muted">{{ $category->description }}</p>
                @endif
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="50%">Question</th>
                            <th>Evaluation Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($category->questions as $question)
                            <tr>
                                <td>{{ $question->question_text }}</td>
                                <td>
                                    @switch($question->evaluation_type)
                                        @case('hrd_to_manager')
                                            HRD to Manager
                                            @break
                                        @case('manager_to_employee')
                                            Manager to Employee
                                            @break
                                        @case('employee_to_manager')
                                            Employee to Manager
                                            @break
                                        @case('manager_to_hrd')
                                            Manager to HRD
                                            @break
                                        @default
                                            Unknown
                                    @endswitch
                                </td>
                                <td>
                                    <span class="badge badge-{{ $question->is_active ? 'success' : 'secondary' }}">
                                        {{ $question->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('hrd.performance.questions.edit', $question) }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('hrd.performance.questions.destroy', $question) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this question?')">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No questions in this category.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center">
                <p>No question categories found.</p>
                <a href="{{ route('hrd.performance.categories.create') }}" class="btn btn-primary">
                    Create your first category
                </a>
            </div>
        </div>
    @endforelse
</div>
@endsection