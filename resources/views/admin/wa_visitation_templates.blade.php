@extends('layouts.admin.app')

@section('title', 'Visitation Templates')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Visitation WhatsApp Templates</h4>
                    <p class="text-muted mb-4">Each WhatsApp session can have its own clinic mapping and template. If only one active template exists, it will be used as the fallback for visitations without a clinic mapping.</p>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="alert alert-light border">
                        <strong>Available variables:</strong>
                        <div class="mt-2">
                            @foreach($availableVariables as $variable)
                                <span class="badge badge-secondary mr-1 mb-1">{{ $variable }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="row">
                        @forelse($sessions as $session)
                            @php($templateConfig = $session->visitationTemplate)
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100 border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="mb-1">{{ $session->label ?: $session->client_id }}</h5>
                                                <div class="text-muted small">Client ID: {{ $session->client_id }}</div>
                                            </div>
                                            @if($templateConfig && $templateConfig->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </div>

                                        <form method="POST" action="{{ route('admin.wa_visitation_templates.update', $session) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="form-group">
                                                <label>Clinic Mapping</label>
                                                <select name="klinik_id" class="form-control">
                                                    <option value="">Use as fallback only</option>
                                                    @foreach($kliniks as $klinik)
                                                        <option value="{{ $klinik->id }}" {{ (string) optional($templateConfig)->klinik_id === (string) $klinik->id ? 'selected' : '' }}>{{ $klinik->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Template</label>
                                                <textarea name="template" rows="12" class="form-control" required>{{ old('template', optional($templateConfig)->template ?: $defaultTemplate) }}</textarea>
                                            </div>

                                            <div class="form-group form-check">
                                                <input type="checkbox" class="form-check-input" id="is_active_{{ $session->id }}" name="is_active" value="1" {{ optional($templateConfig)->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active_{{ $session->id }}">Enable automatic visitation message for this session</label>
                                            </div>

                                            <button type="submit" class="btn btn-primary">Save Template</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">No WhatsApp sessions found yet. Add a session from the WhatsApp Test page first.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection