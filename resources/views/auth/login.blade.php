<x-guest-layout>
   <div class="min-h-screen w-full flex items-center justify-center
        bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ asset('images/zentask-bg-reg.png') }}')">
        <div class="bg-white/ backdrop-blur-md shadow-2xl rounded-3xl
            w-full max-w-6xl grid lg:grid-cols-2 overflow-hidden border border-white/10">

            {{-- Left Illustration --}}
            <div class="relative flex items-center justify-center bg-cover bg-center bg-no-repeat"
                 style="background-image: url('{{ asset('images/zentask-panel.png') }}');">
                <div class="absolute inset-0 bg-black/10 backdrop-blur-[px]"></div>
            </div>

            {{-- Right Content --}}
            <div class="px-16 py-20 flex flex-col justify-center text-black">
                <h1 class="text-4xl font-extrabold mb-4">
                    Selamat datang kembali
                </h1>

                <p class="text-4x3 text-white/ text leading-relaxed mb-8">
                    Masuk ke akun Zentask-mu untuk melihat dan mengelola semua aktivitas yang sudah kamu rencanakan.
                </p>

                {{-- Status / error (optional, kalau pakai komponen Breeze) --}}
                @if (session('status'))
                    <div class="mb-4 text-sm text-green-300">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label class="text- font-medium">Email</label>
                        <input
                            class="w-full mt-1 border-blue-300 rounded-xl focus:ring-blue-400 focus:border-blue-400
                                   text-black px-3 py-2"
                            type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="text-black font-medium">Password</label>
                        <input
                            class="w-full mt-1 border-blue-300 rounded-xl focus:ring-blue-400 focus:border-blue-400
                                   text-black px-3 py-2"
                            type="password" name="password" required autocomplete="current-password">
                    </div>

                    {{-- Remember Me --}}
                    <div class="flex items-center justify-between text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="remember"
                                   class="rounded border-gray-300 text-yellow-400 shadow-sm focus:ring-yellow-400">
                            <span class="text-white/80">Ingat saya</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-white-300 hover:text-blue-200 underline"
                               href="{{ route('password.request') }}">
                                Lupa password?
                            </a>
                        @endif
                    </div>

                    {{-- Button Login --}}
                    <button
                        class="w-full bg-blue-400 hover:bg-white text-black font-semibold py-3 rounded-xl transition">
                        Masuk
                    </button>

                    <p class="text-sm text-white text-center">
                        Belum punya akun?
                        <a href="{{ route('register') }}" class="text-white underline">
                            Daftar sekarang
                        </a>
                    </p>
                </form>
            </div>

        </div>
    </div>
</x-guest-layout>
