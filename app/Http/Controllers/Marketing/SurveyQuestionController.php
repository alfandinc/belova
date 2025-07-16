<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Survey\SurveyQuestion;
use Yajra\DataTables\DataTables;

class SurveyQuestionController extends Controller
{
    public function index()
    {
        return view('marketing.survey_questions.index');
    }

    public function datatable(Request $request)
    {
        $query = SurveyQuestion::query();
        if ($request->has('klinik') && $request->klinik !== null && $request->klinik !== '') {
            $query->where(function($q) use ($request) {
                $q->where('klinik_name', $request->klinik)
                  ->orWhereNull('klinik_name');
            });
        }
        // No filter at all if 'All Klinik' (empty string)
        return DataTables::of($query)
            ->addColumn('action', function($row) {
                return '<button class="btn btn-sm btn-primary edit-btn" data-id="'.$row->id.'">Edit</button> '
                    .'<button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'">Delete</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|string',
            'options' => 'nullable|array',
            'order' => 'nullable|integer',
            'klinik_name' => 'nullable|string',
        ]);
        if(isset($data['options'])) {
            $data['options'] = json_encode($data['options']);
        }
        $question = SurveyQuestion::create($data);
        return response()->json(['success' => true, 'data' => $question]);
    }

    public function update(Request $request, $id)
    {
        $question = SurveyQuestion::findOrFail($id);
        $data = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|string',
            'options' => 'nullable|array',
            'order' => 'nullable|integer',
            'klinik_name' => 'nullable|string',
        ]);
        if(isset($data['options'])) {
            $data['options'] = json_encode($data['options']);
        }
        $question->update($data);
        return response()->json(['success' => true, 'data' => $question]);
    }

    public function destroy($id)
    {
        $question = SurveyQuestion::findOrFail($id);
        $question->delete();
        return response()->json(['success' => true]);
    }
}
