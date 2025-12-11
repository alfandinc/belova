<?php
namespace App\Http\Controllers\Workdoc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workdoc\NotulensiRapat;
use App\Models\Workdoc\NotulensiRapatTodo;
use Illuminate\Support\Facades\Auth;

class NotulensiRapatController extends Controller
{
    // Display index view (AJAX DataTable)
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datatables()->of(NotulensiRapat::with('createdBy'))
                ->addColumn('action', function ($row) {
                    $user = Auth::user();
                    $isCreator = $row->created_by === $user->id;
                    $hasRole = $user->hasRole(['Manager', 'Hrd', 'Ceo']);
                    $url = route('workdoc.notulensi-rapat.show', $row->id);
                    if ($isCreator || $hasRole) {
                        return '<a href="' . $url . '" class="btn btn-sm btn-primary">View</a>';
                    }
                    // non-authorized: show disabled button
                    return '<button class="btn btn-sm btn-secondary" disabled>View</button>';
                })
                ->addColumn('created_by', function ($row) {
                    return $row->createdBy->name ?? '-';
                })
                ->addColumn('notulensi_btn', function ($row) {
                    return '<button class="btn btn-info btn-sm show-notulensi" data-notulensi="'.e($row->notulen).'">Notulensi</button>';
                })
                ->rawColumns(['action', 'notulensi_btn'])
                ->make(true);
        }
        return view('workdoc.notulensi_rapat.index');
    }


    // Show notulensi rapat detail (reuse create view for display)
    public function show($id)
    {
        $notulensi = NotulensiRapat::findOrFail($id);
        $user = Auth::user();
        $isCreator = $notulensi->created_by === $user->id;
        $hasRole = $user->hasRole(['Manager', 'Hrd', 'Ceo','Admin']);
        if (! $isCreator && ! $hasRole) {
            abort(403);
        }
        return view('workdoc.notulensi_rapat.create', compact('notulensi'));
    }

    // Show create form
    public function create()
    {
        return view('workdoc.notulensi_rapat.create');
    }

    // Store new notulensi rapat
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'notulen' => 'required|string',
            'memo' => 'nullable|string',
        ]);
        $data = $request->only(['title', 'date', 'notulen', 'memo']);
    $data['created_by'] = Auth::id();
        NotulensiRapat::create($data);
        return response()->json(['success' => true]);
    }

    // Update existing notulensi
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'notulen' => 'required|string',
            'memo' => 'nullable|string',
        ]);
        $notulensi = NotulensiRapat::findOrFail($id);
        $user = Auth::user();
        $isCreator = $notulensi->created_by === $user->id;
        $hasRole = $user->hasRole(['Manager', 'Hrd', 'Ceo','Admin']);
        if (! $isCreator && ! $hasRole) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $notulensi->title = $request->input('title');
        $notulensi->date = $request->input('date');
        $notulensi->notulen = $request->input('notulen');
        $notulensi->memo = $request->input('memo');
        $notulensi->save();
        return response()->json(['success' => true]);
    }

        // AJAX DataTable for todos
    public function todos(Request $request, $id)
    {
        $notulensi = NotulensiRapat::findOrFail($id);
        if ($request->ajax()) {
            return datatables()->of($notulensi->todos())
                ->addIndexColumn()
                ->addColumn('status', function ($row) {
                    if ($row->status === 'done') {
                        return '<span class="badge bg-success">Done</span>';
                    } elseif ($row->status === 'pending') {
                        return '<span class="badge bg-warning text-dark">Pending</span>';
                    } else {
                        return '<span class="badge bg-secondary">' . ucfirst($row->status) . '</span>';
                    }
                })
                ->addColumn('action', function ($row) {
                    $approveBtn = '';
                    if ($row->status !== 'done') {
                        $approveBtn = '<button class="btn btn-sm btn-success approve-todo" data-id="'.$row->id.'">Approve</button> ';
                    }
                    $approvedText = $row->status === 'done' && $row->approved_by ? '<span class="text-success">Approved by '.($row->approved_by_user->name ?? '').'</span>' : '';
                    $deleteBtn = '';
                    if ($row->status !== 'done') {
                        $deleteBtn = '<button class="btn btn-sm btn-danger delete-todo" data-id="'.$row->id.'">Delete</button>';
                    }
                    return $approveBtn . $approvedText . ' ' . $deleteBtn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        abort(404);
    }

    // Store new todo
    public function storeTodo(Request $request, $id)
    {
        $request->validate([
            'task' => 'required|string|max:255',
            'status' => 'required|string',
            'due_date' => 'nullable|date',
        ]);
        $notulensi = NotulensiRapat::findOrFail($id);
        $notulensi->todos()->create($request->only(['task', 'status', 'due_date']));
        return response()->json(['success' => true]);
    }

    // Delete todo
    public function deleteTodo($notulensiId, $todoId)
    {
        $todo = NotulensiRapatTodo::where('notulensi_rapat_id', $notulensiId)->findOrFail($todoId);
        $todo->delete();
        return response()->json(['success' => true]);
    }

        // Approve To-Do
    public function approveTodo($notulensiId, $todoId)
    {
        $todo = \App\Models\Workdoc\NotulensiRapatTodo::where('notulensi_rapat_id', $notulensiId)->findOrFail($todoId);
    $todo->status = 'done';
    $todo->approved_by = Auth::id();
        $todo->save();
    return response()->json(['success' => true, 'approved_by' => Auth::user()->name]);
    }


}
