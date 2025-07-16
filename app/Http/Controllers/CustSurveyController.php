<?php

namespace App\Http\Controllers;

use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustSurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $klinik = $request->query('klinik');
        $questions = SurveyQuestion::where(function($q) use ($klinik) {
            $q->whereNull('klinik_name');
            if ($klinik) {
                $q->orWhere('klinik_name', $klinik);
            }
        })->orderBy('order')->get();
        return view('customer.index', compact('questions', 'klinik'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $klinik = $request->input('klinik');
        $questions = SurveyQuestion::where(function($q) use ($klinik) {
            $q->whereNull('klinik_name');
            if ($klinik) {
                $q->orWhere('klinik_name', $klinik);
            }
        })->orderBy('order')->get();
        $submissionId = Str::uuid()->toString();
        $now = now();
        $answers = [];
        foreach ($questions as $q) {
            $field = 'q' . $q->id;
            if ($q->question_type === 'emoji_scale') {
                $request->validate([
                    $field => 'required|integer|min:1|max:5',
                ]);
            } elseif ($q->question_type === 'multiple_choice') {
                $request->validate([
                    $field => 'required|string|max:30',
                ]);
            }
            $answers[] = [
                'question_id' => $q->id,
                'answer' => $request->input($field),
                'submission_id' => $submissionId,
                'submitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        SurveyAnswer::insert($answers);
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Terima kasih atas partisipasi Anda!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
