@extends('layouts.admin.app')

@section('title', 'Conversation')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Conversation for Pasien: {{ $pasien_id }}</h4>
                    <div class="list-group">
                        @foreach($messages as $m)
                            <div class="list-group-item">
                                <div><strong>{{ $m->direction == 'out' ? 'SENT' : 'RECEIVED' }}</strong> - {{ $m->created_at }}</div>
                                <div>{{ $m->body }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
