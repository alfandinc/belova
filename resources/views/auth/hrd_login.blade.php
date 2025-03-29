@extends('layouts.app')

@section('title', 'Login | HRD Belova')

@section('content')
<div class="container">
    <div class="row vh-100 d-flex justify-content-center">
        <div class="col-12 align-self-center">
            <div class="row">
                <div class="col-lg-5 mx-auto">
                    <div class="card rounded-3" style="border-radius: 15px; overflow: hidden;">
                        <div class="card-body p-0 auth-header-box" style="background-color: #0d6efd;">
                            <div class="text-center p-3">
                                <h1 class="mt-3 mb-1 font-weight-semibold text-white"><i class="fas fa-user-friends"></i> HRD Login</h1>
                                <h5 class="text-white">Welcome to HRD Belova, login to continue.</h5>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <input type="hidden" name="module" value="hrd">
                                <div class="form-group mb-2">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" class="form-control" id="email" placeholder="Enter Email">
                                </div>

                                <div class="form-group mb-2">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter password">
                                </div>

                                <button class="btn btn-primary btn-block" type="submit">Log In</button>
                                <a href="{{ url('/') }}" class="btn btn-secondary btn-block mt-2">Back to Main Menu</a>
                            </form>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
