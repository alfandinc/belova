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
            ],
            [
                'title' => 'Belova Skin',
                'description' => 'Skin and beauty center dashboard with the same executive modules, ready for future custom flows.',
                'route' => route('ceo-dashboard.belova_skin.index'),
                'theme' => 'theme-skin',
            ],
            [
                'title' => 'Belova Dental',
                'description' => 'Dental clinic analytics for visits, revenue, doctors, patients, and operational trends.',
                'route' => route('ceo-dashboard.belova_dental.index'),
                'theme' => 'theme-dental',
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
            padding: 12px 0 24px;
        }

        .ceo-home-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 18px;
        }

        .ceo-clinic-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .ceo-bottom-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr);
            gap: 18px;
        }

        .ceo-card-link,
        .ceo-quick-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .ceo-quick-link.is-disabled {
            cursor: default;
        }

        .ceo-card {
            position: relative;
            min-height: 328px;
            border-radius: 22px;
            overflow: hidden;
            padding: 18px;
            color: #fff;
            background: linear-gradient(180deg, #124cab 0%, #0f4aa6 100%);
            box-shadow: 0 18px 42px rgba(17, 69, 146, 0.18);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .ceo-card:hover,
        .ceo-quick-link:hover .ceo-quick-card {
            transform: translateY(-4px);
            box-shadow: 0 22px 48px rgba(17, 69, 146, 0.22);
        }

        .ceo-card-title {
            font-size: 2rem;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 14px;
        }

        .ceo-card-copy {
            font-size: 0.95rem;
            line-height: 1.35;
            color: rgba(255, 255, 255, 0.92);
            margin-top: 14px;
            max-width: 90%;
        }

        .ceo-card-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .ceo-card-cta::after {
            content: '>'; 
            font-size: 0.95rem;
        }

        .ceo-card-visual {
            position: relative;
            height: 146px;
            border-radius: 18px;
            overflow: hidden;
            background: linear-gradient(180deg, #bee4ff 0%, #d9f1ff 100%);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.28);
        }

        .ceo-card-visual::before,
        .ceo-card-visual::after {
            content: '';
            position: absolute;
            background: rgba(255, 255, 255, 0.92);
            border-radius: 999px;
        }

        .ceo-card-visual::before {
            width: 52px;
            height: 24px;
            top: 18px;
            left: 78px;
            box-shadow: -36px 12px 0 -8px rgba(255, 255, 255, 0.82), 32px 6px 0 -10px rgba(255, 255, 255, 0.88);
        }

        .ceo-card-visual::after {
            left: -8%;
            right: -8%;
            bottom: -22px;
            height: 66px;
            background: radial-gradient(circle at 18% 0, #c4df6a 0 18%, transparent 19%),
                radial-gradient(circle at 42% 0, #b1cf3d 0 19%, transparent 20%),
                radial-gradient(circle at 70% 0, #9ec300 0 18%, transparent 19%),
                linear-gradient(180deg, #9bbd00 0%, #7fa400 100%);
            border-radius: 56% 44% 0 0 / 34% 34% 0 0;
        }

        .ceo-card.theme-skin {
            background: linear-gradient(180deg, #0e7285 0%, #0d6271 100%);
            box-shadow: 0 18px 42px rgba(10, 97, 113, 0.2);
        }

        .ceo-card.theme-dental {
            background: linear-gradient(180deg, #0d5b9f 0%, #084987 100%);
            box-shadow: 0 18px 42px rgba(8, 73, 135, 0.2);
        }

        .ceo-card.theme-bcl {
            min-height: 212px;
            background: linear-gradient(180deg, #147b63 0%, #0f6954 100%);
            box-shadow: 0 18px 42px rgba(15, 105, 84, 0.2);
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
            color: #fff;
            background: linear-gradient(180deg, #174f9d 0%, #14488e 100%);
            box-shadow: 0 14px 30px rgba(20, 72, 142, 0.16);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .ceo-quick-link.is-disabled .ceo-quick-card {
            opacity: 0.72;
            box-shadow: none;
        }

        .ceo-quick-title {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.05;
        }

        .ceo-quick-copy {
            margin-top: 6px;
            font-size: 0.87rem;
            line-height: 1.3;
            color: rgba(255, 255, 255, 0.88);
        }

        .theme-hrd .ceo-quick-card {
            background: linear-gradient(180deg, #8b3f12 0%, #77350e 100%);
            box-shadow: 0 14px 30px rgba(139, 63, 18, 0.18);
        }

        .theme-task .ceo-quick-card {
            background: linear-gradient(180deg, #5c2b9b 0%, #4d2383 100%);
            box-shadow: 0 14px 30px rgba(92, 43, 155, 0.18);
        }

        .theme-workdoc .ceo-quick-card {
            background: linear-gradient(180deg, #3b5d21 0%, #314d1b 100%);
            box-shadow: 0 14px 30px rgba(59, 93, 33, 0.18);
        }

        @media (max-width: 1199.98px) {
            .ceo-clinic-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ceo-bottom-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 767.98px) {
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

            .ceo-card-title,
            .ceo-quick-title {
                font-size: 1.7rem;
            }
        }
    </style>

    <div class="container-fluid ceo-home-shell">
        <div class="ceo-home-grid">
            <div class="ceo-clinic-grid">
                @foreach ($clinicCards as $card)
                    <a href="{{ $card['route'] }}" class="ceo-card-link" aria-label="{{ $card['title'] }}">
                        <article class="ceo-card {{ $card['theme'] }}">
                            <div class="ceo-card-title">{{ $card['title'] }}</div>
                            <div class="ceo-card-visual" aria-hidden="true"></div>
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
                            <div class="ceo-card-visual" aria-hidden="true"></div>
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
