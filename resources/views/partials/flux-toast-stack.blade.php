<<<<<<< HEAD
=======
{{-- Toast notifications (teleported to body to escape transform contexts) --}}
>>>>>>> change
@teleport('body')
@persist('toast')
    <div
        x-data="{ toasts: [], remove(id) { this.toasts = this.toasts.filter(t => t.id !== id) } }"
        x-on:toast.window="
            let t = { id: Date.now(), ...$event.detail };
            toasts.push(t);
            setTimeout(() => remove(t.id), t.duration || 4000)
        "
        style="position:fixed; bottom:1rem; right:1rem; z-index:9999; display:flex; flex-direction:column-reverse; gap:0.5rem; width:20rem;"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-[-0.5rem]"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
<<<<<<< HEAD
                class="rounded-xl border px-4 py-3 shadow-lg text-sm flex items-start gap-3"
                :class="{
                    'bg-indigo-50 border-indigo-200 text-indigo-900': toast.variant === 'success',
                    'bg-amber-50 border-amber-200 text-amber-900': toast.variant === 'warning',
                    'bg-red-50 border-red-200 text-red-900': toast.variant === 'danger',
                    'bg-white border-slate-200 text-slate-800': !toast.variant || toast.variant === 'info',
                }"
            >
                <template x-if="toast.variant === 'success'">
                    <svg class="size-5 shrink-0 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
=======
                class="rounded-lg border px-4 py-3 shadow-lg text-sm flex items-start gap-3"
                :class="{
                    'bg-emerald-50 border-emerald-200 text-emerald-800': toast.variant === 'success',
                    'bg-amber-50 border-amber-200 text-amber-800': toast.variant === 'warning',
                    'bg-red-50 border-red-200 text-red-800': toast.variant === 'danger',
                    'bg-white border-zinc-200 text-zinc-800': !toast.variant || toast.variant === 'info',
                }"
            >
                <template x-if="toast.variant === 'success'">
                    <svg class="size-5 shrink-0 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
>>>>>>> change
                </template>
                <template x-if="toast.variant === 'warning'">
                    <svg class="size-5 shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                </template>
                <template x-if="toast.variant === 'danger'">
                    <svg class="size-5 shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                </template>
                <div class="flex-1">
                    <p x-show="toast.heading" x-text="toast.heading" class="font-semibold"></p>
                    <p x-text="toast.text"></p>
                </div>
<<<<<<< HEAD
                <button type="button" x-on:click="remove(toast.id)" class="shrink-0 opacity-50 hover:opacity-100">
=======
                <button x-on:click="remove(toast.id)" class="shrink-0 opacity-50 hover:opacity-100">
>>>>>>> change
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
                </button>
            </div>
        </template>
    </div>
@endpersist
@endteleport
