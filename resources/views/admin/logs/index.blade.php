<x-layouts.app :title="__('Logs')">
    <div class="space-y-6">
        <div>
            <flux:heading>{{ __('Logs') }}</flux:heading>
            <flux:subheading>{{ __('Visualize e baixe os arquivos de log da aplicacao.') }}</flux:subheading>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-3">{{ __('Arquivo') }}</th>
                            <th class="px-4 py-3">{{ __('Tamanho') }}</th>
                            <th class="px-4 py-3">{{ __('Atualizado em') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Acoes') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $log['name'] }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $log['size_human'] }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $log['modified_at'] }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a
                                            href="{{ route('admin.logs.download', ['file' => $log['name']]) }}"
                                            class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        >
                                            {{ __('Baixar') }}
                                        </a>
                                        <button
                                            type="button"
                                            class="view-log rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-zinc-800"
                                            data-url="{{ route('admin.logs.tail', ['file' => $log['name']]) }}"
                                            data-name="{{ $log['name'] }}"
                                        >
                                            {{ __('Ver') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400" colspan="4">
                                    {{ __('Nenhum arquivo de log encontrado.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <dialog id="log-modal" class="w-full max-w-5xl rounded-xl bg-white p-0 shadow-xl backdrop:bg-black/40 dark:bg-zinc-900">
        <div class="space-y-4 p-6">
            <div class="space-y-1">
                <h2 id="log-modal-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('Visualizar log') }}
                </h2>
                <p id="log-modal-subtitle" class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Ultimos :size do arquivo selecionado.', ['size' => $tailBytesHuman]) }}
                </p>
            </div>

            <pre id="log-content" class="max-h-[70vh] overflow-auto rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-xs leading-5 text-zinc-800 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></pre>

            <div class="flex items-center justify-end">
                <button type="button" id="close-log-modal" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    {{ __('Fechar') }}
                </button>
            </div>
        </div>
    </dialog>

    <script>
        (() => {
            const modal = document.getElementById('log-modal');
            const title = document.getElementById('log-modal-title');
            const subtitle = document.getElementById('log-modal-subtitle');
            const content = document.getElementById('log-content');
            const closeButton = document.getElementById('close-log-modal');
            const viewButtons = document.querySelectorAll('.view-log');
            const tailBytesHuman = @json($tailBytesHuman);
            let activeController = null;

            const closeModal = () => {
                if (activeController) {
                    activeController.abort();
                    activeController = null;
                }

                modal.close();
            };

            const openLog = async (button) => {
                const url = button.dataset.url;
                const name = button.dataset.name || 'log';

                title.textContent = `Log: ${name}`;
                subtitle.textContent = `Ultimos ${tailBytesHuman} deste arquivo.`;
                content.textContent = 'Carregando...';
                modal.showModal();

                if (activeController) {
                    activeController.abort();
                }

                activeController = new AbortController();

                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: activeController.signal,
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const text = await response.text();
                    content.textContent = text === '' ? '[arquivo vazio]' : text;
                    content.scrollTop = content.scrollHeight;
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    content.textContent = 'Nao foi possivel carregar o log.';
                }
            };

            viewButtons.forEach((button) => {
                button.addEventListener('click', () => openLog(button));
            });

            closeButton.addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        })();
    </script>
</x-layouts.app>
