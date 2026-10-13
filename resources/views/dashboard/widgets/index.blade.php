@extends('layouts.erm.app')

@section('title', 'Widget Dashboard')

@section('navbar')
    @include('dashboard.navbar')
@endsection

@section('content')
    <style>
        .wa {
            --wa-ink: #1e293b;
            --wa-muted: #64748b;
            --wa-faint: #94a3b8;
            --wa-line: #e2e8f0;
            --wa-surface: #ffffff;
            --wa-surface-2: #f8fafc;
            --wa-accent: #2563eb;
            --wa-accent-soft: #eff6ff;
            color: var(--wa-ink);
            padding-top: 20px;
            padding-bottom: 32px;
        }

        html.theme-dark .wa {
            --wa-ink: #edf0f5;
            --wa-muted: #9aa3b8;
            --wa-faint: #7d869c;
            --wa-line: #3a4160;
            --wa-surface: #2c3144;
            --wa-surface-2: #262b3c;
            --wa-accent: #6ea8f7;
            --wa-accent-soft: rgba(57, 135, 229, 0.16);
        }

        html.theme-dark .wa .wa-role span::before {
            background: transparent;
            border-color: var(--wa-faint);
        }

        html.theme-dark .wa .wa-search {
            background: var(--wa-surface);
            color: var(--wa-ink);
        }

        .wa-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .wa-title {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .wa-sub {
            margin: 4px 0 0;
            color: var(--wa-muted);
            font-size: 0.875rem;
            max-width: 720px;
        }

        .wa-search {
            min-width: 240px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid var(--wa-line);
            font-size: 0.875rem;
        }

        .wa-card {
            background: var(--wa-surface);
            border: 1px solid var(--wa-line);
            border-radius: 14px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 16px rgba(15, 23, 42, 0.05);
            margin-bottom: 12px;
        }

        .wa-widget {
            display: flex;
            gap: 20px;
            padding: 16px 20px;
            flex-wrap: wrap;
        }

        .wa-widget-info {
            flex: 0 0 260px;
            min-width: 0;
        }

        .wa-widget-name {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        .wa-widget-desc {
            font-size: 0.8rem;
            color: var(--wa-muted);
            margin: 2px 0 8px;
        }

        .wa-meta {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .wa-tag {
            padding: 1px 8px;
            border-radius: 6px;
            background: var(--wa-surface-2);
            border: 1px solid var(--wa-line);
            font-size: 0.72rem;
            color: var(--wa-muted);
        }

        .wa-tag.is-warning {
            background: #fffbeb;
            border-color: #fde68a;
            color: #b45309;
        }

        .wa-roles {
            flex: 1 1 400px;
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 8px;
        }

        .wa-role {
            position: relative;
            margin: 0;
            cursor: pointer;
        }

        .wa-role input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .wa-role span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid var(--wa-line);
            background: var(--wa-surface);
            color: var(--wa-muted);
            font-size: 0.8rem;
            font-weight: 600;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            user-select: none;
        }

        .wa-role span small {
            font-weight: 600;
            color: var(--wa-faint);
        }

        .wa-role span::before {
            content: '';
            width: 14px;
            height: 14px;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            background: #fff;
            flex: 0 0 14px;
        }

        .wa-role:hover span {
            border-color: #cbd5e1;
        }

        .wa-role input:checked + span {
            background: var(--wa-accent-soft);
            border-color: #93c5fd;
            color: var(--wa-accent);
        }

        .wa-role input:checked + span small {
            color: #60a5fa;
        }

        .wa-role input:checked + span::before {
            border-color: var(--wa-accent);
            background: var(--wa-accent) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.5 8.5l3 3 6-7'/%3E%3C/svg%3E") center / 11px no-repeat;
        }

        .wa-role input:focus-visible + span {
            outline: 2px solid var(--wa-accent);
            outline-offset: 2px;
        }

        .wa-role.is-saving span {
            opacity: 0.55;
        }

        .wa-note {
            font-size: 0.8rem;
            color: var(--wa-muted);
            margin-top: 16px;
        }

        .wa-note code {
            font-size: 0.78rem;
        }

        @media (max-width: 575.98px) {
            .wa-widget-info {
                flex-basis: 100%;
            }

            .wa-search {
                width: 100%;
            }
        }
    </style>

    <div class="container-fluid wa">
        <div class="wa-head">
            <div>
                <h1 class="wa-title">Widget Dashboard</h1>
                <p class="wa-sub">Centang role yang boleh melihat setiap widget di dashboard utama. User melihat gabungan widget dari semua role yang dimilikinya. Perubahan langsung tersimpan.</p>
            </div>
            <input type="search" id="wa-search" class="form-control wa-search" placeholder="Cari widget atau role…" aria-label="Cari widget atau role">
        </div>

        @forelse ($widgets as $widget)
            <div class="wa-card wa-widget" data-search="{{ strtolower($widget->widget_name . ' ' . $widget->description) }}">
                <div class="wa-widget-info">
                    <h2 class="wa-widget-name">{{ $widget->widget_name }}</h2>
                    <p class="wa-widget-desc">{{ $widget->description ?: '—' }}</p>
                    <div class="wa-meta">
                        <span class="wa-tag">Lebar {{ $widget->column_span }}/12</span>
                        <span class="wa-tag">Urutan {{ $widget->order }}</span>
                        @unless ($widget->view_exists)
                            <span class="wa-tag is-warning">View tidak ditemukan</span>
                        @endunless
                    </div>
                </div>
                <div class="wa-roles">
                    @foreach ($roles as $role)
                        <label class="wa-role" data-role="{{ strtolower($role->name) }}">
                            <input type="checkbox"
                                   class="js-widget-access"
                                   data-widget="{{ $widget->key }}"
                                   data-role-id="{{ $role->id }}"
                                   @checked(!empty($granted[$role->id][$widget->permission]))>
                            <span>{{ $role->name }} <small>{{ $role->users_count }}</small></span>
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="wa-card wa-widget">
                <p class="mb-0 text-muted">Belum ada widget. Tambahkan widget di <code>config/dashboard_widgets.php</code>.</p>
            </div>
        @endforelse

        <p class="wa-note">
            Widget baru ditambahkan lewat kode (<code>config/dashboard_widgets.php</code>), jadi selalu sama di lokal dan production.
            Angka di samping role adalah jumlah user yang memiliki role tersebut.
        </p>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {
            var csrf = $('meta[name="csrf-token"]').attr('content');

            function toast(icon, title) {
                if (window.Swal) {
                    Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 2200 });
                }
            }

            $(document).on('change', '.js-widget-access', function () {
                var input = $(this);
                var label = input.closest('.wa-role');
                var granted = input.is(':checked');

                label.addClass('is-saving');
                input.prop('disabled', true);

                $.ajax({
                    url: @json(route('dashboard.widgets.toggle')),
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    data: {
                        widget: input.data('widget'),
                        role_id: input.data('role-id'),
                        granted: granted ? 1 : 0
                    }
                }).done(function (response) {
                    toast('success', response.message || 'Tersimpan');
                }).fail(function () {
                    input.prop('checked', !granted);
                    toast('error', 'Gagal menyimpan. Silakan coba lagi.');
                }).always(function () {
                    label.removeClass('is-saving');
                    input.prop('disabled', false);
                });
            });

            // Filter by widget name/description, or by role name (then only matching role pills stay visible)
            $('#wa-search').on('input', function () {
                var q = $.trim($(this).val()).toLowerCase();

                $('.wa-widget[data-search]').each(function () {
                    var card = $(this);
                    var widgetMatch = !q || card.data('search').indexOf(q) !== -1;
                    var roleMatches = card.find('.wa-role').filter(function () {
                        return $(this).data('role').indexOf(q) !== -1;
                    });

                    card.find('.wa-role').toggle(widgetMatch || !q || roleMatches.length === 0);
                    if (!widgetMatch && roleMatches.length) {
                        roleMatches.show();
                    }
                    card.toggle(widgetMatch || roleMatches.length > 0);
                });
            });
        });
    </script>
@endsection
