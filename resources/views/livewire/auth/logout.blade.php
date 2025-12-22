<div class="mt-auto p-4">
    <button
        type="button"
        wire:click="logout"
        wire:loading.attr="disabled"
        class="w-full rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold hover:bg-slate-800 disabled:opacity-60"
    >
        <span wire:loading.remove>
            <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
        </span>

        <span wire:loading>
            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Logging out...
        </span>
    </button>
</div>
