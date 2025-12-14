<x-guest-layout>
   <div class="min-h-screen w-full flex items-center justify-center
        bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ asset('images/zentask-bg-reg.png') }}')">
        <div class="bg-white/10 backdrop-blur-md shadow-2xl rounded-3xl
            w-full max-w-6xl grid lg:grid-cols-2 overflow-hidden border border-white/10">

            {{-- Left Illustration --}}
            <div class="relative flex items-center justify-center bg-cover bg-center bg-no-repeat"
                 style="background-image: url('{{ asset('images/zentask-panel.png') }}');">
                <div class="absolute inset-0 bg-black/10 backdrop-[2px]"></div>
            </div>

            {{-- Right Content --}}
            <div class="px-16 py-20 flex flex-col justify-center text-black">
                <h1 class="text-4xl font-extrabold mb-4">
                    Daftar Akun Baru
                </h1>

                <p class="text-xl text-black/100 leading-relaxed mb-10">
                    Kelola kegiatanmu, tetap fokus pada tujuanmu dan capai produktivitas terbaikmu setiap hari.
                </p>

                {{-- Status / error (optional) --}}
                @if (session('status'))
                    <div class="mb-4 text-sm text-green-300">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    {{-- Nama Lengkap --}}
                    <div>
                        <label class="text-black font-medium">Nama Lengkap</label>
                        <input
                            class="w-full mt-1 border-blue-300 rounded-xl focus:ring-blue-400 focus:border-blue-400
                                   text-black px-3 py-2"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Masukkan nama lengkap">
                        @error('name')
                            <span class="text-red-300 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="text-black font-medium">Email</label>
                        <input
                            class="w-full mt-1 border-blue-300 rounded-xl focus:ring-blue-400 focus:border-blue-400
                                   text-black px-3 py-2"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="username"
                            placeholder="farid@gmail.com">
                        @error('email')
                            <span class="text-red-300 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="text-black font-medium">Password</label>
                        <input
                            class="w-full mt-1 border-blue-300 rounded-xl focus:ring-blue-400 focus:border-blue-400
                                   text-black px-3 py-2"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••">
                        @error('password')
                            <span class="text-red-300 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Konfirmasi Password --}}
                    <div>
                        <label class="text-black font-medium">Konfirmasi Password</label>
                        <input
                            class="w-full mt-1 border-blue-300 rounded-xl focus:ring-blue-400 focus:border-blue-400
                                   text-black px-3 py-2"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••">
                        @error('password_confirmation')
                            <span class="text-red-300 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Button Register --}}
                    <button
                        class="w-full bg-blue-400 hover:bg-white text-black font-semibold py-3 rounded-xl transition">
                        Daftar Sekarang
                    </button>

                    <p class="text-sm text-white text-center">
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="text-black underline">
                            Masuk di sini
                        </a>
                    </p>
                </form>
            </div>

        </div>
    </div>
</x-guest-layout>
