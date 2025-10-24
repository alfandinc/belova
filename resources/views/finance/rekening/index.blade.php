@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h3>Daftar Rekening</h3>
            <p>This is a placeholder page for finance.rekening.index. The Rekening list is managed inline from the Pengajuan form.</p>
            <a href="{{ route('finance.pengajuan.index') }}" class="btn btn-secondary">Back to Pengajuan</a>
        </div>
    </div>
</div>
@endsection
