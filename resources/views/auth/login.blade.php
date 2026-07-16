<x-layout.guest :title="'Login - MGI chat'">
    <div class="relative flex min-h-screen items-center justify-center bg-white p-4">
        <div class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-100 bg-white px-8 py-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#8B1E3F]">
                    <svg class="h-7 w-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                    </svg>
                </div>
                <h1 class="font-display text-3xl font-semibold tracking-tight text-[#5C1529]">MGI chat</h1>
                <p class="mt-2 text-sm text-slate-500">WhatsApp CRM · entre na sua conta</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-5 p-8">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">E-mail</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Senha</label>
                    <input type="password" name="password" id="password" required
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-500">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#8B1E3F] focus:ring-[#8B1E3F]">
                    Lembrar de mim
                </label>
                <button type="submit"
                        class="w-full rounded-2xl bg-[#8B1E3F] px-4 py-3.5 text-sm font-bold text-white transition hover:opacity-90">
                    Entrar
                </button>
            </form>

            @if ($errors->any())
                <div class="mx-8 mb-8 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif
        </div>
    </div>
</x-layout.guest>
