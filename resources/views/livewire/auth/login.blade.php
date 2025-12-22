<div class="w-full max-w-lg">
    {{-- Header --}}
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 h-14 w-14 rounded-2xl bg-slate-900 flex items-center justify-center">
            <i class="fas fa-lock text-white text-xl"></i>
        </div>

        <h1 class="text-3xl font-bold text-slate-900">Masuk</h1>
        <p class="mt-2 text-base text-slate-600">
            Silakan login untuk melanjutkan
        </p>
    </div>

    {{-- Card --}}
    <div class="rounded-2xl bg-white border border-slate-200 p-8 sm:p-10">
        <form wire:submit.prevent="login" class="space-y-6">

            {{-- EMAIL --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Email
                </label>

                <div class="relative">
                    <span class="absolute inset-y-0 left-4 flex items-center text-slate-400">
                        <i class="fas fa-envelope"></i>
                    </span>

                    <input
                        type="email"
                        wire:model.defer="email"
                        placeholder="nama@email.com"
                        autocomplete="email"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50
                               pl-11 pr-4 py-3 text-base
                               focus:bg-white focus:outline-none focus:border-slate-300"
                    >
                </div>

                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- PASSWORD --}}
            <div x-data="{ show: false }">
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Password
                </label>

                <div class="relative">
                    <span class="absolute inset-y-0 left-4 flex items-center text-slate-400">
                        <i class="fas fa-lock"></i>
                    </span>

                    <input
                        :type="show ? 'text' : 'password'"
                        wire:model.defer="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50
                               pl-11 pr-12 py-3 text-base
                               focus:bg-white focus:outline-none focus:border-slate-300"
                    >

                    {{-- TOGGLE EYE --}}
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute inset-y-0 right-4 flex items-center
                               text-slate-500 hover:text-slate-700"
                        aria-label="Toggle password"
                    >
                        <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>

                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- BUTTON --}}
            <button
                type="submit"
                wire:target="login"
                wire:loading.attr="disabled"
                class="w-full rounded-xl bg-slate-900 py-3
                       text-base font-semibold text-white
                       hover:bg-slate-800 transition
                       flex items-center justify-center gap-2
                       disabled:opacity-70 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="login">
                    <i class="fas fa-right-to-bracket"></i>
                    Login
                </span>

                <span wire:loading wire:target="login" class="flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin"></i>
                    Memproses...
                </span>
            </button>
        </form>

        <div class="mt-8 text-center text-sm text-slate-500">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</div>
