<x-layout.guest :title="'Login - MGI Chat'">
    <main class="grid min-h-screen bg-slate-50 lg:grid-cols-2">
        <section class="order-2 flex items-center justify-center px-5 py-10 sm:px-10 lg:order-1">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-9">
                <div class="mb-8">
                    <div class="mb-5 flex items-center gap-3">
                        <img src="{{ asset('images/mgi-logo-oficial.png') }}"
                             alt="Logo MGI"
                             class="h-10 w-14 rounded-md bg-white object-contain">
                        <div>
                            <p class="text-lg font-extrabold tracking-tight text-[#8B1E3F]">MGI Chat</p>
                            <p class="text-[9px] font-bold uppercase tracking-[0.16em] text-slate-400">WhatsApp CRM</p>
                        </div>
                    </div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-[#8B1E3F]">Acesso ao painel</p>
                    <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Bem-vindo de volta</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Entre para gerenciar seus atendimentos pelo WhatsApp.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="mb-1.5 block text-xs font-bold text-slate-700">E-mail</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                               placeholder="nome@empresa.com"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-[#8B1E3F] focus:ring-3 focus:ring-[#8B1E3F]/8">
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-xs font-bold text-slate-700">Senha</label>
                        <input type="password" name="password" id="password" required
                               placeholder="Digite sua senha"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-[#8B1E3F] focus:ring-3 focus:ring-[#8B1E3F]/8">
                    </div>
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-500">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#8B1E3F] focus:ring-[#8B1E3F]">
                        Manter conectado
                    </label>
                    <button type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#8B1E3F] px-4 py-3 text-sm font-extrabold text-white transition hover:bg-[#721832] focus-visible:outline-[#8B1E3F]">
                        Entrar no painel
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </button>
                </form>

                @if ($errors->any())
                    <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <p class="mt-7 border-t border-slate-100 pt-5 text-center text-[11px] text-slate-400">Ambiente seguro para a equipe MGI</p>
            </div>
        </section>

        <section class="order-1 flex min-h-56 items-center justify-center overflow-hidden border-b border-slate-200 bg-white px-6 py-10 lg:order-2 lg:min-h-screen lg:border-b-0 lg:border-l lg:border-slate-200 lg:px-12">
            <div class="w-full max-w-2xl text-center">
                <img src="{{ asset('images/mgi-logo-oficial.png') }}" alt="MGI — Minas Gerais Participações S.A."
                     class="mx-auto w-full max-w-xl object-contain" fetchpriority="high">
                <div class="mx-auto mt-7 h-px w-16 bg-[#8B1E3F]"></div>
                <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Atendimento inteligente pelo WhatsApp</p>
            </div>
        </section>
    </main>
</x-layout.guest>
