@extends('layouts.dashboard')

@section('content')
    <div class="dashboard-content">
        <div class="dashboard-container">

            {{-- =========================
            [A] HEADER
            ========================== --}}
            <div class="mb-6">
                <h1 class="text-4xl font-extrabold text-blue-200 tracking-tight">Edit Task</h1>
                <p class="text-blue-100/70 mt-1">Ubah judul, kategori, deskripsi, deadline, dan status.</p>
            </div>

            {{-- =========================
            [B] ERROR MESSAGE
            ========================== --}}
            @if ($errors->any())
                <div class="mb-4 p-4 rounded-xl bg-red-500/10 border border-red-400/20 text-red-200 text-sm">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- =========================
            [C] LAYOUT 2 KOLOM (FULL KE KANAN, MINIM SCROLL)
            ========================== --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- =========================
                [C1] FORM CARD (LEBAR, TANPA max-w)
                ========================== --}}
                <div class="lg:col-span-2 rounded-2xl bg-white/5 border border-white/10 backdrop-blur p-6">
                    <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        {{-- Judul --}}
                        <div>
                            <label class="block text-sm text-blue-100/70">Judul</label>
                            <input name="title" type="text" required value="{{ old('title', $task->title) }}"
                                class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-blue-100/40 focus:ring-0 focus:border-blue-300/40">
                        </div>

                        {{-- Category --}}
                        <div>
                            <label class="block text-sm text-blue-100/70">Category</label>
                            <input name="category" type="text" value="{{ old('category', $task->category) }}"
                                class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-blue-100/40 focus:ring-0 focus:border-blue-300/40"
                                placeholder="Contoh: Kuliah, Pribadi, Kerja">
                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label class="block text-sm text-blue-100/70">Deskripsi</label>
                            <textarea name="description" rows="3"
                                class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-blue-100/40 focus:ring-0 focus:border-blue-300/40"
                                placeholder="Catatan singkat...">{{ old('description', $task->description) }}</textarea>
                        </div>

                        {{-- [EDIT] Voice Record (hasil jadi teks) --}}
                        <div class="mt-4">
                            <label class="block text-sm text-blue-100/70">Voice Record (hasil jadi teks)</label>
                            <textarea name="voice_text" rows="3"
                                class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-blue-100/40 focus:ring-0 focus:border-blue-300/40"
                                placeholder="Isi voice record (teks)...">{{ old('voice_text', $task->voice_text) }}</textarea>

                            <p class="mt-2 text-xs text-blue-100/60">
                                *Ini teks hasil rekaman. Bisa diedit manual.
                            </p>
                        </div>

                        {{-- Deadline --}}
                        <div>
                            <label class="block text-sm text-blue-100/70">Deadline</label>
                            <input name="due_date" type="date" value="{{ old('due_date', $task->due_date) }}"
                                class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 text-white focus:ring-0 focus:border-blue-300/40">
                        </div>

                        {{-- Status (model tombol seperti page Tasks) --}}
                        <div>
                            <label class="block text-sm text-blue-100/70">Status</label>
                            <div class="mt-2 flex items-center gap-2">
                                {{-- IN PROGRESS --}}
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="status" value="in_progress" class="hidden peer/inprogress"
                                        @checked(old('status', $task->status) === 'in_progress')>
                                    <span class="text-xs px-4 py-2 rounded-xl border transition cursor-pointer
            peer-checked/inprogress:bg-purple-500/20
            peer-checked/inprogress:border-purple-400/30
            peer-checked/inprogress:text-purple-100
            bg-white/5 border-white/10 text-blue-100/70 hover:bg-white/10">
                                        IN PROGRESS
                                    </span>
                                </label>


                                {{-- SELESAI --}}
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="status" value="completed" class="hidden peer/completed"
                                        @checked(old('status', $task->status) === 'completed')>
                                    <span class="text-xs px-4 py-2 rounded-xl border transition cursor-pointer
                                            peer-checked/completed:bg-green-500/20 peer-checked/completed:border-green-400/30 peer-checked/completed:text-green-100
                                            bg-white/5 border-white/10 text-blue-100/70 hover:bg-white/10">
                                        SELESAI
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit"
                                class="px-6 py-2 rounded-xl bg-blue-500/90 hover:bg-blue-500 text-white font-semibold transition">
                                Simpan
                            </button>

                            <a href="{{ route('tasks.index') }}"
                                class="px-6 py-2 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 text-blue-100 font-semibold transition">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>

                {{-- =========================
                [C2] SIDE CARD (ISI AREA KANAN BIAR GA KOSONG)
                ========================== --}}
                <div class="lg:col-span-1 rounded-2xl bg-white/5 border border-white/10 backdrop-blur p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Ringkasan</h3>

                    <div class="space-y-3 text-sm">

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-blue-100/70">Dibuat</span>
                            <span class="text-blue-100/90">
                                {{ $task->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-blue-100/70">Terakhir update</span>
                            <span class="text-blue-100/90">
                                {{ $task->updated_at->timezone(config('app.timezone'))->translatedFormat('d M Y') }}
                            </span>
                        </div>

                        <div class="pt-3 border-t border-white/10">
                            <div class="text-blue-100/70 mb-2">Status saat ini</div>

                            @if(($task->status ?? '') === 'completed')
                                <span
                                    class="text-xs px-3 py-1 rounded-full bg-green-500/15 border border-green-400/20 text-green-100">
                                    SELESAI
                                </span>
                            @else
                                <span
                                    class="text-xs px-3 py-1 rounded-full bg-purple-500/15 border border-purple-400/20 text-purple-100">
                                    IN PROGRESS
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('tasks.index') }}"
                            class="w-full inline-flex justify-center px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 text-blue-100 font-semibold transition">
                            Kembali ke Tasks
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
