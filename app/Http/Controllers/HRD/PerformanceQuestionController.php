<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PerformanceQuestion;
use App\Models\HRD\PerformanceQuestionCategory;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class PerformanceQuestionController extends Controller
{
    public function index()
    {
        $categories = PerformanceQuestionCategory::with(['questions' => function($query) {
            $query->orderBy('evaluation_type');
        }])->get();
        $evaluationTypes = [
            'hrd_to_manager' => 'HRD to Manager',
            'manager_to_employee' => 'Manager to Employee',
            'employee_to_manager' => 'Employee to Manager',
            'manager_to_hrd' => 'Manager to HRD'
        ];
        return view('hrd.performance.questions.index', compact('categories', 'evaluationTypes'));
    }
    
    public function getGroupedQuestions()
    {
        $categories = PerformanceQuestionCategory::with(['questions' => function($query) {
            $query->orderBy('evaluation_type');
        }])->get();
        
        return response()->json($categories);
    }
    
    public function getCategories(Request $request)
    {
        $categories = PerformanceQuestionCategory::select(['id', 'name', 'description', 'is_active']);
        
        return DataTables::of($categories)
            ->addColumn('action', function ($category) {
                $editBtn = '<button data-id="'.$category->id.'" class="edit-category btn btn-sm btn-primary"><i class="fa fa-edit"></i> Edit</button> ';
                $deleteBtn = '<button data-id="'.$category->id.'" class="delete-category btn btn-sm btn-danger"><i class="fa fa-trash"></i> Delete</button>';
                return $editBtn . $deleteBtn;
            })
            ->addColumn('status', function ($category) {
                return $category->is_active ? 
                    '<span class="badge badge-success">Active</span>' : 
                    '<span class="badge badge-secondary">Inactive</span>';
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }
    
    public function getQuestions(Request $request)
    {
        $questions = PerformanceQuestion::with('category')->select('performance_questions.*');
        
        return DataTables::of($questions)
            ->addColumn('action', function ($question) {
                $editBtn = '<button data-id="'.$question->id.'" class="edit-question btn btn-sm btn-primary"><i class="fa fa-edit"></i> Edit</button> ';
                $deleteBtn = '<button data-id="'.$question->id.'" class="delete-question btn btn-sm btn-danger"><i class="fa fa-trash"></i> Delete</button>';
                return $editBtn . $deleteBtn;
            })
            ->addColumn('category_name', function ($question) {
                return $question->category->name;
            })
            ->addColumn('status', function ($question) {
                return $question->is_active ? 
                    '<span class="badge badge-success">Active</span>' : 
                    '<span class="badge badge-secondary">Inactive</span>';
            })
            ->editColumn('evaluation_type', function ($question) {
                $types = [
                    'hrd_to_manager' => 'HRD to Manager',
                    'manager_to_employee' => 'Manager to Employee',
                    'employee_to_manager' => 'Employee to Manager',
                    'manager_to_hrd' => 'Manager to HRD'
                ];
                return $types[$question->evaluation_type] ?? 'Unknown';
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    // Category Methods
    public function getCategoryById($id)
    {
        $category = PerformanceQuestionCategory::findOrFail($id);
        return response()->json($category);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $category = PerformanceQuestionCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Question category created successfully',
            'category' => $category
        ]);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = PerformanceQuestionCategory::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Question category updated successfully',
            'category' => $category
        ]);
    }

    public function destroyCategory($id)
    {
        $category = PerformanceQuestionCategory::findOrFail($id);
        
        // Check if questions exist in this category
        if ($category->questions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing questions'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question category deleted successfully'
        ]);
    }

    // Question Methods
    public function getQuestionById($id)
    {
        $question = PerformanceQuestion::findOrFail($id);
        return response()->json($question);
    }

    public function getActiveCategories()
    {
        $categories = PerformanceQuestionCategory::where('is_active', true)->get();
        
        // Log for debugging
        Log::info('Active categories fetched: ' . $categories->count());
        
        return response()->json($categories);
    }

    public function storeQuestion(Request $request)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'category_id' => 'required|exists:performance_question_categories,id',
            'evaluation_type' => 'required|in:hrd_to_manager,manager_to_employee,employee_to_manager,manager_to_hrd',
            'is_active' => 'boolean'
        ]);

        $question = PerformanceQuestion::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully',
            'question' => $question
        ]);
    }

    public function updateQuestion(Request $request, $id)
    {
        $question = PerformanceQuestion::findOrFail($id);
        
        $validated = $request->validate([
            'question_text' => 'required|string',
            'category_id' => 'required|exists:performance_question_categories,id',
            'evaluation_type' => 'required|in:hrd_to_manager,manager_to_employee,employee_to_manager,manager_to_hrd',
            'is_active' => 'boolean'
        ]);

        $question->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully',
            'question' => $question
        ]);
    }

    public function destroyQuestion($id)
    {
        $question = PerformanceQuestion::findOrFail($id);
        
        // Check if scores exist for this question
        if ($question->scores()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete question with existing scores'
            ], 422);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully'
        ]);
    }
}
