<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\Promo;
use Yajra\DataTables\Facades\DataTables;

class PromoController extends Controller
{
    public function index()
    {
        return view('marketing.promo.index');
    }

    public function data(Request $request)
    {
        $query = Promo::query();

        $start = $request->get('start_date');
        $end = $request->get('end_date');
        if ($start && $end) {
            // filter promos that overlap the selected period
            $query->where(function($q) use ($start, $end){
                $q->where(function($q2) use ($start, $end){
                    $q2->whereNotNull('start_date')->whereNotNull('end_date')
                        ->where('start_date','<=',$end)
                        ->where('end_date','>=',$start);
                })
                ->orWhere(function($q2) use ($start, $end){
                    $q2->whereNotNull('start_date')->whereNull('end_date')
                        ->whereBetween('start_date', [$start, $end]);
                })
                ->orWhere(function($q2) use ($start, $end){
                    $q2->whereNull('start_date')->whereNotNull('end_date')
                        ->whereBetween('end_date', [$start, $end]);
                });
            });
        }
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                $edit = '<button class="btn btn-sm btn-primary btn-edit" data-id="'.$row->id.'">Edit</button>';
                $del = '<button class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'">Delete</button>';
                return $edit.' '.$del;
            })
            ->addColumn('date_range', function($row){
                // Use translated month names if available
                try {
                    $start = $row->start_date ? $row->start_date->translatedFormat('j F Y') : '';
                } catch (\Exception $e) {
                    $start = $row->start_date ? $row->start_date->format('j F Y') : '';
                }
                try {
                    $end = $row->end_date ? $row->end_date->translatedFormat('j F Y') : '';
                } catch (\Exception $e) {
                    $end = $row->end_date ? $row->end_date->format('j F Y') : '';
                }
                if ($start && $end) return $start . ' - ' . $end;
                return $start ?: $end ?: '';
            })
            ->editColumn('name', function($row){
                $title = e($row->name);
                $desc = $row->description ? '<br><small class="text-muted">'.e($row->description).'</small>' : '';
                return '<strong>'.$title.'</strong>'.$desc;
            })
            ->editColumn('status', function($row){
                $s = strtolower($row->status);
                if ($s === 'active') {
                    $class = 'badge badge-success';
                } elseif ($s === 'inactive') {      
                    $class = 'badge badge-secondary';
                } elseif ($s === 'draft') {
                    $class = 'badge badge-warning';
                } else {
                    $class = 'badge badge-light';
                }
                return '<span class="'.$class.'">'.ucfirst($s).'</span>';
            })
            ->rawColumns(['actions','status','name'])
            ->make(true);
    }

    public function show(Promo $promo)
    {
        $data = $promo->toArray();
        $data['start_date'] = $promo->start_date ? $promo->start_date->format('Y-m-d') : null;
        $data['end_date'] = $promo->end_date ? $promo->end_date->format('Y-m-d') : null;
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|string|in:active,inactive,draft',
        ]);

        $promo = Promo::create($data);

        return response()->json(['success' => true, 'data' => $promo]);
    }

    public function update(Request $request, Promo $promo)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|string|in:active,inactive,draft',
        ]);

        $promo->update($data);

        return response()->json(['success' => true, 'data' => $promo]);
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();
        return response()->json(['success' => true]);
    }
}
