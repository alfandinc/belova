<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Survey\SurveyQuestion;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;

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
            ->addColumn('average_score', function($row) {
                if ($row->question_type === 'multiple_choice') {
                    $options = $row->options;
                    if (is_string($options)) {
                        $options = json_decode($options, true) ?: [];
                    }
                    if (!is_array($options)) {
                        $options = [];
                    }
                    $counts = [];
                    foreach ($options as $opt) {
                        $count = $row->answers()->where('answer', $opt)->count();
                        $counts[] = '<li style="font-size:1.1em;font-weight:bold;">'.e($opt).' = '.$count.'</li>';
                    }
                    return '<ul style="padding-left:18px;margin:0;">'.implode('', $counts).'</ul>';
                } else {
                    $avg = $row->averageScore();
                    if (!$avg) return '<span>-</span>';
                    $score = number_format($avg, 2);
                    $color = ($avg > 3) ? 'success' : 'danger';
                    return '<span class="badge badge-'.$color.'" style="font-size:1.6em;padding:10px 22px;font-weight:bold;">'.$score.'</span>';
                }
            })
            ->addColumn('action', function($row) {
                return '<button class="btn btn-sm btn-primary edit-btn" data-id="'.$row->id.'">Edit</button> '
                    .'<button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'">Delete</button>';
            })
            ->rawColumns(['average_score', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $input = $request->all();
        if (isset($input['options']) && is_string($input['options'])) {
            $decoded = json_decode($input['options'], true);
            if (is_array($decoded)) {
                $input['options'] = $decoded;
            }
        }
        $data = Validator::make($input, [
            'question_text' => 'required|string',
            'question_type' => 'required|string',
            'options' => 'nullable|array',
            'order' => 'nullable|integer',
            'klinik_name' => 'nullable|string',
        ])->validate();
        if (isset($data['options'])) {
            // If options is a string, decode it first
            if (is_string($data['options'])) {
                $decoded = json_decode($data['options'], true);
                if (is_array($decoded)) {
                    $data['options'] = $decoded;
                }
            }
            $data['options'] = json_encode($data['options']);
        }
        $question = SurveyQuestion::create($data);
        return response()->json(['success' => true, 'data' => $question]);
    }

    public function update(Request $request, $id)
    {
        $question = SurveyQuestion::findOrFail($id);
        $input = $request->all();
        if (isset($input['options']) && is_string($input['options'])) {
            $decoded = json_decode($input['options'], true);
            if (is_array($decoded)) {
                $input['options'] = $decoded;
            }
        }
        $data = Validator::make($input, [
            'question_text' => 'required|string',
            'question_type' => 'required|string',
            'options' => 'nullable|array',
            'order' => 'nullable|integer',
            'klinik_name' => 'nullable|string',
        ])->validate();
        if (isset($data['options'])) {
            // If options is a string, decode it first
            if (is_string($data['options'])) {
                $decoded = json_decode($data['options'], true);
                if (is_array($decoded)) {
                    $data['options'] = $decoded;
                }
            }
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
