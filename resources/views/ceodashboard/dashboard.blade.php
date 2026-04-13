@extends('layouts.erm.app')

@section('title', 'CEO Dashboard')

@section('navbar')
    @include('layouts.ceodashboard.navbar')
@endsection

@section('content')
    @php
        $canOpenWorkdoc = auth()->check() && auth()->user()->hasAnyRole(['Hrd', 'Manager', 'Employee', 'Admin']);

        $clinicCards = [
            [
                'title' => 'Premiere Belova',
                'description' => 'Executive view for clinic visitation, revenue, doctor, patient, and social media performance.',
                'route' => route('ceo-dashboard.premiere_belova.index'),
                'theme' => 'theme-premiere',
                'image' => asset('img/bg-premiere.jpg'),
            ],
            [
                'title' => 'Belova Skin',
                'description' => 'Skin and beauty center dashboard with the same executive modules, ready for future custom flows.',
                'route' => route('ceo-dashboard.belova_skin.index'),
                'theme' => 'theme-skin',
                'image' => asset('img/bg-belovaskin.jpg'),
            ],
            [
                'title' => 'Belova Dental',
                'description' => 'Dental clinic analytics for visits, revenue, doctors, patients, and operational trends.',
                'route' => route('ceo-dashboard.belova_dental.index'),
                'theme' => 'theme-dental',
                'image' => asset('img/bg-dental.jpg'),
            ],
        ];

        $quickLinks = [
            [
                'title' => 'Kepegawaian',
                'description' => 'Open the HR dashboard and workforce monitoring page.',
                'route' => route('hrd.dashboard'),
                'theme' => 'theme-hrd',
            ],
            [
                'title' => 'Daily Task',
                'description' => 'Review manager daily task reporting and execution status.',
                'route' => route('ceo-dashboard.daily-tasks.index'),
                'theme' => 'theme-task',
            ],
            [
                'title' => 'Dokumen Kerja',
                'description' => $canOpenWorkdoc
                    ? 'Open the work documentation hub and administrative files.'
                    : 'Akses untuk modul workdoc belum dibuka pada role CEO ini.',
                'route' => $canOpenWorkdoc ? route('workdoc.dashboard') : null,
                'theme' => 'theme-workdoc',
                'disabled' => ! $canOpenWorkdoc,
            ],
        ];
    @endphp

    <style>
        .ceo-home-shell {
            padding: 16px 0 24px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .ceo-home-panel {
            background: linear-gradient(180deg, #ffffff 0%, #f6f8fb 100%);
            border: 1px solid #e5eaf1;
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 16px 40px rgba(43, 57, 79, 0.08);
        }

        .ceo-home-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 18px;
        }

        .ceo-page-header {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 2px;
        }

        .ceo-page-title {
            margin: 0;
            font-size: 1.85rem;
            line-height: 1.15;
            font-weight: 700;
            color: #233041;
        }

        .ceo-page-subtitle {
            margin: 0;
            font-size: 0.92rem;
            line-height: 1.45;
            color: #627080;
            max-width: 760px;
        }

        .ceo-clinic-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            align-items: stretch;
        }

        .ceo-bottom-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr);
            gap: 18px;
            align-items: start;
        }

        .ceo-card-link,
        .ceo-quick-link {
            text-decoration: none;
            color: inherit;
            display: block;
            min-width: 0;
        }

        .ceo-quick-link.is-disabled {
            cursor: default;
        }

        .ceo-card {
            position: relative;
            min-height: 328px;
            height: 100%;
            border-radius: 22px;
            overflow: hidden;
            padding: 18px;
            color: #fff;
            background: linear-gradient(180deg, #2f7de1 0%, #1450ad 100%);
            box-shadow: 0 18px 42px rgba(20, 80, 173, 0.2);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .ceo-card:hover,
        .ceo-quick-link:hover .ceo-quick-card {
            transform: translateY(-4px);
            box-shadow: 0 22px 48px rgba(46, 66, 94, 0.2);
        }

        .ceo-card-title {
            font-size: 1.6rem;
            line-height: 1.1;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin-bottom: 14px;
        }

        .ceo-card-copy {
            font-size: 0.88rem;
            line-height: 1.45;
            color: rgba(255, 255, 255, 0.92);
            margin-top: 14px;
            max-width: 90%;
        }

        .ceo-card-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            font-size: 0.86rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .ceo-card-cta::after {
            content: '>'; 
            font-size: 0.86rem;
        }

        .ceo-card-visual {
            position: relative;
            height: 146px;
            border-radius: 18px;
            overflow: hidden;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
        }

        .ceo-card-visual::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.12) 0%, rgba(15, 23, 42, 0.08) 100%);
        }

        .ceo-card.theme-skin {
            background: linear-gradient(180deg, #9a5de2 0%, #6d37b8 100%);
            box-shadow: 0 18px 42px rgba(109, 55, 184, 0.2);
        }

        .ceo-card.theme-dental {
            background: linear-gradient(180deg, #f5a13d 0%, #df6d17 100%);
            box-shadow: 0 18px 42px rgba(223, 109, 23, 0.22);
        }

        .ceo-card.theme-bcl {
            min-height: 212px;
            background: linear-gradient(180deg, #ef89b0 0%, #d94d8d 100%);
            box-shadow: 0 18px 42px rgba(217, 77, 141, 0.22);
        }

        .ceo-card.theme-bcl .ceo-card-inner {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(220px, 1fr);
            gap: 18px;
            align-items: center;
            height: 100%;
        }

        .ceo-quick-stack {
            display: grid;
            gap: 12px;
        }

        .ceo-quick-card {
            border-radius: 16px;
            padding: 16px 18px;
            min-height: 60px;
            height: 100%;
            color: #253140;
            background: linear-gradient(180deg, #f3f5f8 0%, #dfe4ea 100%);
            box-shadow: 0 14px 30px rgba(90, 100, 114, 0.12);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .ceo-quick-link.is-disabled .ceo-quick-card {
            opacity: 0.72;
            box-shadow: none;
        }

        .ceo-quick-title {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.15;
            color: #253140;
        }

        .ceo-quick-copy {
            margin-top: 6px;
            font-size: 0.81rem;
            line-height: 1.4;
            color: #5f6c7b;
        }

        .theme-hrd .ceo-quick-card {
            background: linear-gradient(180deg, #f4f5f7 0%, #dde2e8 100%);
            box-shadow: 0 14px 30px rgba(90, 100, 114, 0.12);
        }

        .theme-task .ceo-quick-card {
            background: linear-gradient(180deg, #f4f5f7 0%, #dde2e8 100%);
            box-shadow: 0 14px 30px rgba(90, 100, 114, 0.12);
        }

        .theme-workdoc .ceo-quick-card {
            background: linear-gradient(180deg, #f4f5f7 0%, #dde2e8 100%);
            box-shadow: 0 14px 30px rgba(90, 100, 114, 0.12);
        }

        @media (max-width: 1199.98px) {
            .ceo-home-panel {
                padding: 22px;
            }

            .ceo-clinic-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ceo-bottom-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 767.98px) {
            .ceo-home-shell {
                padding: 12px 0 20px;
            }

            .ceo-home-panel {
                padding: 16px;
                border-radius: 18px;
            }

            .ceo-home-grid,
            .ceo-clinic-grid,
            .ceo-bottom-grid,
            .ceo-quick-stack {
                gap: 14px;
            }

            .ceo-clinic-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .ceo-card,
            .ceo-card.theme-bcl {
                min-height: auto;
            }

            .ceo-card.theme-bcl .ceo-card-inner {
                grid-template-columns: minmax(0, 1fr);
            }

            .ceo-page-title {
                font-size: 1.55rem;
            }

            .ceo-card-title {
                font-size: 1.4rem;
            }

            .ceo-quick-title {
                font-size: 1.05rem;
            }
        }
    </style>

    <div class="container-fluid ceo-home-shell">
        <div class="ceo-home-panel">
            <div class="ceo-home-grid">
                <div class="ceo-page-header">
                    <h1 class="ceo-page-title">CEO Dashboard</h1>
                    <p class="ceo-page-subtitle">Ringkasan akses cepat untuk seluruh unit Belova, mulai dari dashboard klinik, kos BCL, hingga modul operasional pendukung.</p>
                </div>

                <div class="ceo-clinic-grid">
                    @foreach ($clinicCards as $card)
                        <a href="{{ $card['route'] }}" class="ceo-card-link" aria-label="{{ $card['title'] }}">
                            <article class="ceo-card {{ $card['theme'] }}">
                                <div class="ceo-card-title">{{ $card['title'] }}</div>
                                <div class="ceo-card-visual" style="background-image: url('{{ $card['image'] }}');" aria-hidden="true"></div>
                                <div class="ceo-card-copy">{{ $card['description'] }}</div>
                                <div class="ceo-card-cta">View Dashboard</div>
                            </article>
                        </a>
                    @endforeach
                </div>

                <div class="ceo-bottom-grid">
                    <a href="{{ route('ceo-dashboard.bcl.index') }}" class="ceo-card-link" aria-label="Belova Center Living">
                        <article class="ceo-card theme-bcl">
                            <div class="ceo-card-inner">
                                <div>
                                    <div class="ceo-card-title">Kos BCL</div>
                                    <div class="ceo-card-copy">Monitor room occupancy, renter movement, cashflow, and inventory maintenance for Belova Center Living.</div>
                                    <div class="ceo-card-cta">View Dashboard</div>
                                </div>
                                <div class="ceo-card-visual" style="background-image: url('{{ asset('img/bg-bcl.jpg') }}');" aria-hidden="true"></div>
                            </div>
                        </article>
                    </a>

                    <div class="ceo-quick-stack">
                        @foreach ($quickLinks as $link)
                            @if (! empty($link['disabled']))
                                <div class="ceo-quick-link is-disabled {{ $link['theme'] }}" aria-label="{{ $link['title'] }}">
                                    <article class="ceo-quick-card">
                                        <div class="ceo-quick-title">{{ $link['title'] }}</div>
                                        <div class="ceo-quick-copy">{{ $link['description'] }}</div>
                                    </article>
                                </div>
                            @else
                                <a href="{{ $link['route'] }}" class="ceo-quick-link {{ $link['theme'] }}" aria-label="{{ $link['title'] }}">
                                    <article class="ceo-quick-card">
                                        <div class="ceo-quick-title">{{ $link['title'] }}</div>
                                        <div class="ceo-quick-copy">{{ $link['description'] }}</div>
                                    </article>
                                </a>
                            @endif
                        @endforeach
                    </div>
            </div>
        </div>
    </div>
@endsection
