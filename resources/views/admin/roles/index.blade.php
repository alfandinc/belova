@extends('layouts.erm.app')

@section('title', 'Manage Roles')

@section('content')
<div class="container">
    <h2>Role Management</h2>

    <form action="{{ route('admin.roles.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Role Name:</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary mt-2">Add Role</button>
    </form>

    <hr>

    <h4>Existing Roles</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Role Name</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roles as $role)
                <tr>
                    <td>{{ $role->id }}</td>
                    <td>{{ $role->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
