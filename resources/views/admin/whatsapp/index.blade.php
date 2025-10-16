<?php /* WhatsApp admin UI removed. Integration disabled. */ ?>
@extends('layouts.admin')

@section('title', 'WhatsApp (Disabled)')

@section('content')
    <div class="container">
        <div class="alert alert-warning mt-4">
            <h5 class="mb-1">WhatsApp integration removed</h5>
            <p class="mb-0">The WhatsApp / Baileys integration and its admin UI have been disabled or removed from this application. If you need to fully delete remaining server-side files (for example the Node microservice or session files), run the cleanup steps provided in the project README or follow the instructions from the maintainer.</p>
        </div>
    </div>
@endsection