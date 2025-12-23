<div>
    {{-- Tombol di sidebar --}}
    <div class="mt-auto p-4">
        <button
            type="button"
            @click="$dispatch('confirm-logout')"
            class="w-full rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold hover:bg-slate-800 disabled:opacity-60 cursor-pointer"
        >
            <span wire:loading.remove>
                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
            </span>

            <span wire:loading>
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Logging out...
            </span>
        </button>
    </div>

    {{-- Modal logic tetap di file ini, tapi UI modal di-teleport ke body --}}
    <div
        x-data="{
            open: false,
            loading: false,
            show() {
                this.open = true;
                this.$nextTick(() => this.$refs.confirmBtn?.focus());
            },
            close() {
                if (this.loading) return;
                this.open = false;
            },
            async confirm() {
                if (this.loading) return;
                this.loading = true;

                // hide cepat
                this.open = false;

                try {
                    await $wire.call('logout');
                } finally {
                    this.loading = false;
                }
            }
        }"
        x-on:confirm-logout.window="show()"
        x-on:keydown.escape.window="open && close()"
        x-on:keydown.enter.window.prevent="open && confirm()"
    >
        <template x-teleport="body">
            <div>
                {{-- Backdrop --}}
                <div
                    x-show="open"
                    x-transition.opacity
                    class="fixed inset-0 z-[9998] bg-black/40"
                    @click="close()"
                    style="display:none;"
                ></div>

                {{-- Modal --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                    style="display:none;"
                >
                    <div
                        class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden"
                        @click.stop
                    >
                        <div class="p-5 flex items-start gap-3">
                            <div class="h-10 w-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="text-lg font-bold text-slate-900">Logout?</div>
                                <div class="mt-1 text-sm text-slate-600">
                                    Kamu yakin mau keluar dari akun ini?
                                </div>
                            </div>
                        </div>

                        <div class="px-5 pb-5 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                class="h-10 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 cursor-pointer"
                                @click="close()"
                                :disabled="loading"
                            >
                                Batal
                            </button>

                            <button
                                x-ref="confirmBtn"
                                type="button"
                                class="h-10 px-4 rounded-xl bg-slate-900 text-white hover:bg-slate-800 inline-flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                                @click="confirm()"
                                :disabled="loading"
                            >
                                <i class="fa-solid fa-spinner fa-spin" x-show="loading" x-cloak></i>
                                <span x-text="loading ? 'Logging out...' : 'Ya, Logout'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
