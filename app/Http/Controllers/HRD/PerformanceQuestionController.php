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
            'manager_to_hrd' => 'Manager to HRD',
            'ceo_to_hrd' => 'CEO to HRD',
            // CEO evaluating a manager should reuse the same question set as CEO -> HRD
            'ceo_to_manager' => 'CEO to Manager'
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
    
    /**
     * Get all questions with their categories for the modal display
     */
    public function getAllQuestions()
    {
        $questions = PerformanceQuestion::with('category')
            ->select(
                'performance_questions.id',
                'performance_questions.question_text',
                'performance_questions.evaluation_type',
                'performance_questions.question_type',
                'performance_questions.is_active',
                'performance_questions.category_id',
                'performance_question_categories.name as category_name',
                'performance_question_categories.description as category_description'
            )
            ->join('performance_question_categories', 'performance_question_categories.id', '=', 'performance_questions.category_id')
            ->orderBy('performance_question_categories.name')
            ->orderBy('performance_questions.evaluation_type')
            ->get();
        
        return response()->json($questions);
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
                    'manager_to_hrd' => 'Manager to HRD',
                    'ceo_to_hrd' => 'CEO to HRD',
                    'ceo_to_manager' => 'CEO to Manager'
                ];
                return $types[$question->evaluation_type] ?? 'Unknown';
            })
            ->addColumn('question_type_display', function ($question) {
                return ucfirst($question->question_type);
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

    /**
     * Return questions filtered by evaluation type grouped by category.
     * Example: /questions/by-evaluation/manager_to_employee
     */
    public function getQuestionsByEvaluationType($evaluationType)
    {
        // Validate allowed types to avoid accidental exposure
        $allowed = [
            'hrd_to_manager',
            'manager_to_employee',
            'employee_to_manager',
            'manager_to_hrd',
            'ceo_to_hrd',
            'ceo_to_manager'
        ];

        if (!in_array($evaluationType, $allowed)) {
            return response()->json(["message" => "Invalid evaluation type"], 400);
        }

        // If CEO is evaluating a manager, reuse the CEO->HRD question set
        $queryEvaluationType = $evaluationType === 'ceo_to_manager' ? 'ceo_to_hrd' : $evaluationType;

        $categories = PerformanceQuestionCategory::with(['questions' => function($q) use ($queryEvaluationType) {
            // Only include active questions for the preview
            $q->where('evaluation_type', $queryEvaluationType)->where('is_active', true)->orderBy('question_type');
        }])->get()->filter(function($cat) {
            // only include categories that have questions for the requested type
            return $cat->questions->isNotEmpty();
        })->values();

        return response()->json($categories);
    }

    public function storeQuestion(Request $request)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'category_id' => 'required|exists:performance_question_categories,id',
            'question_type' => 'required|in:score,text',
            'evaluation_type' => 'required|in:hrd_to_manager,manager_to_employee,employee_to_manager,manager_to_hrd,ceo_to_hrd',
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
            'question_type' => 'required|in:score,text',
            'evaluation_type' => 'required|in:hrd_to_manager,manager_to_employee,employee_to_manager,manager_to_hrd,ceo_to_hrd',
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
