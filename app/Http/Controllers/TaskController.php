<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Menampilkan task milik user saat ini
     */
    public function index()
    {
        $tasks = Task::where('user_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->get();

        return view('dashboard', compact('tasks'));
    }

    /**
     * Simpan task baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        Task::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
        ]);

        return redirect()->back()->with('success', 'Task berhasil ditambahkan!');
    }

    /**
     * Update status task (completed / pending)
     */
    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            return abort(403); // Forbidden untuk task milik user lain
        }

        $request->validate([
            'status' => 'required|in:pending,completed',
        ]);

        $task->update([
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Status task diperbarui!');
    }

    /**
     * Hapus task
     */
    public function destroy(Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            return abort(403);
        }

        $task->delete();

        return redirect()->back()->with('success', 'Task berhasil dihapus!');
    }
}
