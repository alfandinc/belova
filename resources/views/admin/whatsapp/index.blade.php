@extends('layouts.admin.app')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title">WhatsApp - Send Message</h3>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                    @if(strpos(session('error'), 'Node.js v22') !== false)
                        <br><br>
                        <strong>Quick Fix:</strong>
                        <ol>
                            <li>Download Node.js v18 LTS from <a href="https://nodejs.org/" target="_blank">nodejs.org</a></li>
                            <li>Uninstall current Node.js v22 from Windows Settings > Apps</li>
                            <li>Install Node.js v18 LTS</li>
                            <li>Restart this page and try again</li>
                        </ol>
                    @endif
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            <!-- Service Control Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Service Control</h5>
                    <div class="btn-group" role="group">
                        <form method="POST" action="{{ route('admin.whatsapp.start') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">Start Service</button>
                        </form>
                        <form method="POST" action="{{ route('admin.whatsapp.stop') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-danger ml-2">Stop Service</button>
                        </form>
                        <a href="{{ route('admin.whatsapp.debug') }}" class="btn btn-info ml-2" target="_blank">Debug Info</a>
                    </div>
                    <small class="form-text text-muted">Use these buttons to start or stop the WhatsApp background service. Click Debug Info to troubleshoot issues.</small>
                </div>
            </div>

            <hr>

            <!-- Send Message Section -->
            <h5>Send Message</h5>
            <form method="POST" action="{{ route('admin.whatsapp.send') }}">
                @csrf
                <div class="form-group">
                    <label for="number">Phone number (with country code, e.g. 62812...)</label>
                    <input type="text" name="number" id="number" class="form-control" placeholder="62812..." required>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" rows="5" class="form-control" placeholder="Your message"></textarea>
                </div>

                <button class="btn btn-primary" type="submit">Send</button>
            </form>
        </div>
    </div>
</div>
@endsection