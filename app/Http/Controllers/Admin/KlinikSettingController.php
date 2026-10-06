<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\Satusehat\ClinicConfig;
use App\Models\Satusehat\Location;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class KlinikSettingController extends Controller
{
    // SatuSehat form fields (prefixed "ss_" in the request) and their defaults
    private const SATUSEHAT_FIELDS = ['auth_url', 'base_url', 'consent_url', 'client_id', 'client_secret', 'organization_id'];
    private const SATUSEHAT_DEFAULTS = [
        'auth_url' => 'https://api-satusehat.kemkes.go.id/oauth2/v1',
        'base_url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
        'consent_url' => 'https://api-satusehat.kemkes.go.id/consent/v1',
    ];
    // SatuSehat Location form fields (prefixed "loc_" in the request)
    private const LOCATION_FIELDS = ['location_id', 'name', 'identifier_value', 'description', 'province', 'city', 'district', 'village', 'rt', 'rw', 'line', 'postal_code', 'latitude', 'longitude'];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Klinik::query()->select('id', 'nama', 'logo', 'color', 'report_cutoff_time');
            $configs = $this->configsByKlinik();
            $locations = $this->locationsByKlinik();

            return DataTables::of($query)
                ->addColumn('logo_html', function ($row) {
                    if (!$row->logo) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<img src="' . e(asset('storage/' . $row->logo)) . '" alt="Logo" style="max-height:40px; max-width:120px;">';
                })
                ->addColumn('color_html', function ($row) {
                    if (!$row->color) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="d-inline-flex align-items-center">'
                        . '<span style="display:inline-block; width:20px; height:20px; border-radius:4px; border:1px solid rgba(0,0,0,.15); background:' . e($row->color) . ';"></span>'
                        . '<span class="ml-2">' . e($row->color) . '</span>'
                        . '</span>';
                })
                ->addColumn('satusehat_html', function ($row) use ($configs, $locations) {
                    $config = $configs->get($row->id);
                    $location = $locations->get($row->id);
                    if (!$config) {
                        $html = '<span class="badge badge-soft-secondary">Belum dikonfigurasi</span>';
                    } else {
                        $html = '<span class="badge badge-soft-success">Terhubung</span>';
                        if ($config->organization_id) {
                            $html .= '<div class="small text-muted mt-1">Org ' . e($config->organization_id) . '</div>';
                        }
                        if ($config->token && $config->token_expires_at && $config->token_expires_at->isFuture()) {
                            $html .= '<div class="small text-success">Token aktif s/d ' . $config->token_expires_at->format('d M H:i') . '</div>';
                        } else {
                            $html .= '<div class="small text-muted">Token kedaluwarsa / belum diambil</div>';
                        }
                    }
                    $html .= $location
                        ? '<div class="small text-muted"><i class="fas fa-map-marker-alt mr-1"></i>' . e($location->name ?: $location->identifier_value ?: 'Lokasi #' . $location->id) . '</div>'
                        : '<div class="small text-muted"><i class="fas fa-map-marker-alt mr-1"></i>Lokasi belum diatur</div>';
                    return $html;
                })
                ->editColumn('report_cutoff_time', function ($row) {
                    return $row->report_cutoff_time ? substr($row->report_cutoff_time, 0, 5) : '-';
                })
                ->addColumn('actions', function ($row) use ($configs) {
                    $config = $configs->get($row->id);
                    $token = $config
                        ? ' <button type="button" class="btn btn-info btn-sm btn-token-klinik" data-config-id="' . $config->id . '">Token</button>'
                        : '';
                    return '<button type="button" class="btn btn-warning btn-sm btn-edit-klinik" data-id="' . $row->id . '">Edit</button>'
                        . $token
                        . ' <button type="button" class="btn btn-danger btn-sm btn-delete-klinik" data-id="' . $row->id . '" data-name="' . e($row->nama) . '">Delete</button>';
                })
                ->rawColumns(['logo_html', 'color_html', 'satusehat_html', 'actions'])
                ->make(true);
        }

        // SatuSehat configs that no klinik row shows: not linked to a klinik, or a second config for the same klinik
        $shownIds = $this->configsByKlinik()->pluck('id');
        $unlinkedConfigs = ClinicConfig::with('klinik')->whereNotIn('id', $shownIds)->orderBy('id')->get();
        $kliniksWithoutConfig = Klinik::whereNotIn('id', ClinicConfig::whereNotNull('klinik_id')->pluck('klinik_id'))->orderBy('nama')->get();

        // Same for SatuSehat Locations
        $shownLocationIds = $this->locationsByKlinik()->pluck('id');
        $unlinkedLocations = Location::with('klinik')->whereNotIn('id', $shownLocationIds)->orderBy('id')->get();
        $kliniksWithoutLocation = Klinik::whereNotIn('id', Location::whereNotNull('klinik_id')->pluck('klinik_id'))->orderBy('nama')->get();

        return view('admin.klinik_settings.index', compact('unlinkedConfigs', 'kliniksWithoutConfig', 'unlinkedLocations', 'kliniksWithoutLocation'));
    }

    public function show($id)
    {
        $klinik = Klinik::findOrFail($id);
        $config = $this->configFor($klinik->id);
        $location = $this->locationFor($klinik->id);

        return response()->json(array_merge($klinik->toArray(), [
            'location' => $location ? $location->only(array_merge(['id'], self::LOCATION_FIELDS)) : null,
            'logo_url' => $klinik->logo ? asset('storage/' . $klinik->logo) : null,
            'report_cutoff_time' => $klinik->report_cutoff_time ? substr($klinik->report_cutoff_time, 0, 5) : null,
            'satusehat' => $config ? array_merge($config->only(array_merge(['id'], self::SATUSEHAT_FIELDS)), [
                'has_token' => !empty($config->token),
                'token_expires_at' => $config->token_expires_at ? $config->token_expires_at->format('d M Y H:i') : null,
                'token_valid' => $config->token && $config->token_expires_at && $config->token_expires_at->isFuture(),
            ]) : null,
        ]));
    }

    public function store(Request $request)
    {
        $validated = $this->validateKlinik($request);

        $data = [
            'nama' => $validated['nama'],
            'report_cutoff_time' => $validated['report_cutoff_time'] ?? '00:00',
            'color' => $validated['color'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('klinik_logos', 'public');
        }

        $klinik = DB::transaction(function () use ($data, $request) {
            $klinik = Klinik::create($data);
            $this->saveSatusehat($request, $klinik);
            $this->saveLocation($request, $klinik);
            return $klinik;
        });

        return response()->json([
            'success' => true,
            'data' => $klinik,
        ]);
    }

    public function update(Request $request, $id)
    {
        $klinik = Klinik::findOrFail($id);
        $validated = $this->validateKlinik($request, $klinik->id);

        $data = [
            'nama' => $validated['nama'],
            'report_cutoff_time' => $validated['report_cutoff_time'] ?? '00:00',
            'color' => $validated['color'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            $this->deleteLogoFile($klinik->logo);
            $data['logo'] = $request->file('logo')->store('klinik_logos', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogoFile($klinik->logo);
            $data['logo'] = null;
        }

        DB::transaction(function () use ($klinik, $data, $request) {
            $klinik->update($data);
            $this->saveSatusehat($request, $klinik);
            $this->saveLocation($request, $klinik);
        });

        return response()->json([
            'success' => true,
            'data' => $klinik,
        ]);
    }

    public function destroy($id)
    {
        $klinik = Klinik::findOrFail($id);

        if ($klinik->visitation()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Klinik tidak dapat dihapus karena sudah memiliki data kunjungan.',
            ], 422);
        }

        if (ClinicConfig::where('klinik_id', $klinik->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Klinik masih memiliki konfigurasi SatuSehat. Hapus konfigurasinya terlebih dahulu (Edit > tab SatuSehat).',
            ], 422);
        }

        if (Location::where('klinik_id', $klinik->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Klinik masih memiliki Lokasi SatuSehat. Hapus lokasinya terlebih dahulu (Edit > tab SatuSehat).',
            ], 422);
        }

        try {
            $logo = $klinik->logo;
            $klinik->delete();
            $this->deleteLogoFile($logo);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Klinik tidak dapat dihapus karena masih digunakan oleh data lain.',
            ], 422);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    private function validateKlinik(Request $request, $ignoreId = null): array
    {
        $rules = [
            'nama' => 'required|string|max:255|unique:erm_klinik,nama' . ($ignoreId ? ',' . $ignoreId : ''),
            'report_cutoff_time' => 'nullable|date_format:H:i',
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ];
        foreach (self::SATUSEHAT_FIELDS as $field) {
            $rules['ss_' . $field] = 'nullable|string|max:255';
        }
        foreach (self::LOCATION_FIELDS as $field) {
            $rules['loc_' . $field] = in_array($field, ['latitude', 'longitude']) ? 'nullable|numeric' : 'nullable|string';
        }

        return $request->validate($rules);
    }

    /**
     * Update the klinik's SatuSehat config, or create one when credentials are filled in.
     * Only fields sent in the request are changed, so the stored token is never touched here.
     */
    private function saveSatusehat(Request $request, Klinik $klinik): void
    {
        $values = [];
        foreach (self::SATUSEHAT_FIELDS as $field) {
            if ($request->has('ss_' . $field)) {
                $values[$field] = trim((string) $request->input('ss_' . $field));
            }
        }
        if (!$values) {
            return;
        }

        foreach (self::SATUSEHAT_DEFAULTS as $field => $default) {
            if (array_key_exists($field, $values) && $values[$field] === '') {
                $values[$field] = $default;
            }
        }
        foreach (['client_id', 'client_secret', 'organization_id'] as $field) {
            if (array_key_exists($field, $values) && $values[$field] === '') {
                $values[$field] = null;
            }
        }

        $config = $this->configFor($klinik->id);
        if ($config) {
            $config->update($values);
            return;
        }

        $hasCredentials = !empty($values['client_id']) || !empty($values['client_secret']) || !empty($values['organization_id']);
        if ($hasCredentials) {
            ClinicConfig::create(array_merge(self::SATUSEHAT_DEFAULTS, $values, ['klinik_id' => $klinik->id]));
        }
    }

    /**
     * Update the klinik's SatuSehat Location, or create one when it is filled in.
     */
    private function saveLocation(Request $request, Klinik $klinik): void
    {
        $values = [];
        foreach (self::LOCATION_FIELDS as $field) {
            if ($request->has('loc_' . $field)) {
                $value = trim((string) $request->input('loc_' . $field));
                $values[$field] = $value === '' ? null : $value;
            }
        }
        if (!$values) {
            return;
        }

        $location = $this->locationFor($klinik->id);
        if ($location) {
            $location->update($values);
            return;
        }

        if (array_filter($values, fn ($v) => $v !== null)) {
            Location::create(array_merge($values, ['klinik_id' => $klinik->id]));
        }
    }

    // The Location the SatuSehat integration uses for a klinik (first one, same as PasienController)
    private function locationFor($klinikId): ?Location
    {
        return Location::where('klinik_id', $klinikId)->orderBy('id')->first();
    }

    private function locationsByKlinik()
    {
        return Location::whereNotNull('klinik_id')->orderBy('id')->get()
            ->unique('klinik_id')
            ->keyBy('klinik_id');
    }

    // The config the SatuSehat integration uses for a klinik (first one, same as PasienController)
    private function configFor($klinikId): ?ClinicConfig
    {
        return ClinicConfig::where('klinik_id', $klinikId)->orderBy('id')->first();
    }

    private function configsByKlinik()
    {
        return ClinicConfig::whereNotNull('klinik_id')->orderBy('id')->get()
            ->unique('klinik_id')
            ->keyBy('klinik_id');
    }

    private function deleteLogoFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
