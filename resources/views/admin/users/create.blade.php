@extends('layouts.admin.app')

@section('content')
<div class="container">
    <h2>Add User</h2>
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="mb-3">
            <label>Name</label>
            <input type="text" class="form-control" name="name" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" class="form-control" name="password" required>
        </div>        <div class="mb-3">
            <label>Roles</label>
            <div class="row">
                @foreach($roles as $role)
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" 
                            id="role_{{ $role->id }}">
                        <label class="form-check-label" for="role_{{ $role->id }}">
                            {{ $role->name }}
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <button type="submit" class="btn btn-success">Create</button>
    </form>
</div>
@endsection
