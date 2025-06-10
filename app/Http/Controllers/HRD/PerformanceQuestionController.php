<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PerformanceQuestion;
use App\Models\HRD\PerformanceQuestionCategory;

class PerformanceQuestionController extends Controller
{
    public function index()
    {
        $categories = PerformanceQuestionCategory::with('questions')->get();
        return view('hrd.performance.questions.index', compact('categories'));
    }

    // Category Methods
    public function createCategory()
    {
        return view('hrd.performance.questions.create-category');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        PerformanceQuestionCategory::create($validated);

        return redirect()->route('hrd.performance.questions.index')
            ->with('success', 'Question category created successfully');
    }

    public function editCategory(PerformanceQuestionCategory $category)
    {
        return view('hrd.performance.questions.edit-category', compact('category'));
    }

    public function updateCategory(Request $request, PerformanceQuestionCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $category->update($validated);

        return redirect()->route('hrd.performance.questions.index')
            ->with('success', 'Question category updated successfully');
    }

    public function destroyCategory(PerformanceQuestionCategory $category)
    {
        // Check if questions exist in this category
        if ($category->questions()->exists()) {
            return redirect()->route('hrd.performance.questions.index')
                ->with('error', 'Cannot delete category with existing questions');
        }

        $category->delete();

        return redirect()->route('hrd.performance.questions.index')
            ->with('success', 'Question category deleted successfully');
    }

    // Question Methods
    public function createQuestion()
    {
        $categories = PerformanceQuestionCategory::where('is_active', true)->get();
        $evaluationTypes = [
            'hrd_to_manager' => 'HRD to Manager',
            'manager_to_employee' => 'Manager to Employee',
            'employee_to_manager' => 'Employee to Manager',
            'manager_to_hrd' => 'Manager to HRD'
        ];

        return view('hrd.performance.questions.create-question', compact('categories', 'evaluationTypes'));
    }

    public function storeQuestion(Request $request)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'category_id' => 'required|exists:performance_question_categories,id',
            'evaluation_type' => 'required|in:hrd_to_manager,manager_to_employee,employee_to_manager,manager_to_hrd',
            'is_active' => 'boolean'
        ]);

        PerformanceQuestion::create($validated);

        return redirect()->route('hrd.performance.questions.index')
            ->with('success', 'Question created successfully');
    }

    public function editQuestion(PerformanceQuestion $question)
    {
        $categories = PerformanceQuestionCategory::where('is_active', true)->get();
        $evaluationTypes = [
            'hrd_to_manager' => 'HRD to Manager',
            'manager_to_employee' => 'Manager to Employee',
            'employee_to_manager' => 'Employee to Manager',
            'manager_to_hrd' => 'Manager to HRD'
        ];

        return view('hrd.performance.questions.edit-question', compact('question', 'categories', 'evaluationTypes'));
    }

    public function updateQuestion(Request $request, PerformanceQuestion $question)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'category_id' => 'required|exists:performance_question_categories,id',
            'evaluation_type' => 'required|in:hrd_to_manager,manager_to_employee,employee_to_manager,manager_to_hrd',
            'is_active' => 'boolean'
        ]);

        $question->update($validated);

        return redirect()->route('hrd.performance.questions.index')
            ->with('success', 'Question updated successfully');
    }

    public function destroyQuestion(PerformanceQuestion $question)
    {
        // Check if scores exist for this question
        if ($question->scores()->exists()) {
            return redirect()->route('hrd.performance.questions.index')
                ->with('error', 'Cannot delete question with existing scores');
        }

        $question->delete();

        return redirect()->route('hrd.performance.questions.index')
            ->with('success', 'Question deleted successfully');
    }
}
