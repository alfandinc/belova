@extends('layouts.erm.app')

@section('navbar')
    @include('layouts.satusehat.navbar')
@endsection

@section('title', $config->exists ? 'Edit Konfigurasi' : 'Tambah Konfigurasi')

@section('content')
    <div class="container-fluid">
        <div class="row mt-4">
            <div class="col-8">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $config->exists ? 'Edit Konfigurasi Klinik' : 'Tambah Konfigurasi Klinik' }}</h4>
                        <form method="POST" action="{{ $config->exists ? route('satusehat.clinics.update', $config->id) : route('satusehat.clinics.store') }}">
                            @csrf
                            @if($config->exists)
                                @method('PUT')
                            @endif

                            <div class="form-group">
                                <label for="klinik_id">Klinik (opsional)</label>
                                <select name="klinik_id" id="klinik_id" class="form-control">
                                    <option value="">-- Pilih Klinik --</option>
                                    @foreach($kliniks as $k)
                                        <option value="{{ $k->id }}" {{ old('klinik_id', $config->klinik_id) == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="base_url">Base URL</label>
                                <input type="text" name="base_url" id="base_url" class="form-control" value="{{ old('base_url', $config->base_url) }}">
                            </div>

                            <div class="form-group">
                                <label for="auth_url">Auth URL</label>
                                <input type="text" name="auth_url" id="auth_url" class="form-control" value="{{ old('auth_url', $config->auth_url) }}">
                            </div>

                            <div class="form-group">
                                <label for="consent_url">Consent URL</label>
                                <input type="text" name="consent_url" id="consent_url" class="form-control" value="{{ old('consent_url', $config->consent_url) }}">
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="client_id">Client ID</label>
                                    <input type="text" name="client_id" id="client_id" class="form-control" value="{{ old('client_id', $config->client_id) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="client_secret">Client Secret</label>
                                    <input type="text" name="client_secret" id="client_secret" class="form-control" value="{{ old('client_secret', $config->client_secret) }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="organization_id">Organization ID</label>
                                <input type="text" name="organization_id" id="organization_id" class="form-control" value="{{ old('organization_id', $config->organization_id) }}">
                            </div>

                            <div class="form-group">
                                <label for="token">Token (JSON)</label>
                                <textarea name="token" id="token" class="form-control" rows="4">{{ old('token', $config->token) }}</textarea>
                            </div>

                            <button class="btn btn-primary">{{ $config->exists ? 'Update' : 'Simpan' }}</button>
                            <a href="{{ route('satusehat.clinics.index') }}" class="btn btn-secondary">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
