<div class="space-y-4">
    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        Detalhes dos itens processados para este lote.
    </p>

    @if (filled($batchErrorMessage ?? null))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200">
            <span class="font-semibold">Erro do lote:</span>
            {{ $batchErrorMessage }}
        </div>
    @endif

    <livewire:meta-ad-batch-items-table :batch-id="$batchId" :key="'meta-ad-batch-items-' . $batchId" />
</div>
