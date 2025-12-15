<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /* ======================================================
     | 1. INDEX (Dashboard & Tasks Page)
     ====================================================== */
    public function index(Request $request)
    {
        $baseQuery = Task::where('user_id', Auth::id());

        $tasks = (clone $baseQuery)
            ->orderBy('created_at', 'desc')
            ->get();

        // === STATISTIK (SATU SUMBER DATA) ===
        $totalTasks = $tasks->count();

        $completedTasks = $tasks->where('status', 'completed')->count();

        $inProgressTasks = $tasks->where('status', 'in_progress')->count();

        $nearestDeadlineTask = $tasks
            ->whereNotNull('due_date')
            ->sortBy('due_date')
            ->first();

        // Dashboard
        if ($request->routeIs('dashboard')) {
            return view('dashboard', compact(
                'tasks',
                'totalTasks',
                'completedTasks',
                'inProgressTasks',
                'nearestDeadlineTask'
            ));
        }

        // Tasks
        return view('tasks.index', compact(
            'tasks',
            'totalTasks',
            'completedTasks',
            'inProgressTasks',
            'nearestDeadlineTask'
        ));
    }


    /* ======================================================
     | 2. STORE (Buat Aktivitas Baru)
     ====================================================== */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'due_date' => 'required|date',
            'description' => 'required|string',
            'voice_text' => 'nullable|string',
        ]);

        Task::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'category' => $request->category,
            'description' => $request->description,
            'voice_text' => $request->voice_text, // opsional
            'due_date' => $request->due_date,
            'status' => 'in_progress',
        ]);

        return redirect()->back()->with('success', 'Task berhasil ditambahkan!');
    }


    /* ======================================================
     | 3. EDIT (TAMPILKAN FORM EDIT)
     | GET /tasks/{task}/edit
     ====================================================== */
    public function edit(Task $task)
    {
        // Proteksi: pastikan task milik user yang login
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        // Tampilkan halaman edit
        return view('tasks.edit', compact('task'));
    }

    /* ======================================================
     | 4. UPDATE (SIMPAN HASIL EDIT)
     | PUT /tasks/{task}
     ====================================================== */
    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id())
            abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'due_date' => 'required|date',
            'description' => 'required|string',
            'voice_text' => 'nullable|string',
            'status' => 'nullable|in:in_progress,completed',
        ]);

        $task->update([
            'title' => $request->input('title', $task->title),
            'category' => $request->input('category', $task->category),
            'description' => $request->input('description', $task->description),
            'voice_text' => $request->input('voice_text', $task->voice_text),
            'due_date' => $request->input('due_date', $task->due_date),
            'status' => $request->input('status', $task->status),
        ]);

        return redirect()->route('tasks.index')->with('success', 'Task berhasil diperbarui!');
    }
    /* ======================================================
     | 5. UPDATE STATUS CEPAT (BUTTON IN PROGRESS / SELESAI)
     | PUT /tasks/{task}/status
     ====================================================== */
    public function updateStatus(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:in_progress,completed',
        ]);

        $task->update([
            'status' => $request->status,
        ]);

        return redirect()->back();
    }

    /* ======================================================
     | 6. DELETE
     ====================================================== */
    public function destroy(Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        $task->delete();

        return redirect()->back()->with('success', 'Task berhasil dihapus!');
    }
}
