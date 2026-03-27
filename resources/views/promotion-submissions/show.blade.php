<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detalhes da promocao') }}
        </h2>
    </x-slot>

    @php
        $statusMap = [
            'pending' => ['label' => 'Pendente', 'class' => 'bg-slate-100 text-slate-700'],
            'processing' => ['label' => 'Processando', 'class' => 'bg-blue-100 text-blue-700'],
            'needs_info' => ['label' => 'Dados faltando', 'class' => 'bg-amber-100 text-amber-700'],
            'captcha_required' => ['label' => 'Captcha', 'class' => 'bg-orange-100 text-orange-700'],
            'completed' => ['label' => 'Concluido', 'class' => 'bg-green-100 text-green-700'],
            'failed' => ['label' => 'Falhou', 'class' => 'bg-red-100 text-red-700'],
        ];
        $statusData = $statusMap[$submission->status] ?? ['label' => $submission->status, 'class' => 'bg-gray-100 text-gray-700'];
        $needsRefresh = in_array($submission->status, ['pending', 'processing'], true);
        $metadataJson = $submission->metadata
            ? json_encode($submission->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;
    @endphp

    <div class="py-8" @if($needsRefresh) x-data x-init="setTimeout(() => window.location.reload(), 5000)" @endif>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Resumo da execucao</h3>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusData['class'] }}">
                            {{ $statusData['label'] }}
                        </span>
                    </div>

                    <dl class="mt-5 grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                        <div>
                            <dt class="font-medium text-gray-600">Link da promocao</dt>
                            <dd class="mt-1">
                                <a href="{{ $submission->promotion_url }}" target="_blank" rel="noopener noreferrer" class="break-all text-pink-700 underline">
                                    {{ $submission->promotion_url }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Criado em</dt>
                            <dd class="mt-1">{{ $submission->created_at->format('d/m/Y H:i:s') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Inicio</dt>
                            <dd class="mt-1">{{ $submission->started_at?->format('d/m/Y H:i:s') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Fim</dt>
                            <dd class="mt-1">{{ $submission->finished_at?->format('d/m/Y H:i:s') ?? '-' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5">
                        <h4 class="text-sm font-semibold text-gray-800">Mensagem de resultado</h4>
                        <p class="mt-2 text-sm text-gray-700">
                            {{ $submission->result_message ?: 'Aguardando processamento.' }}
                        </p>
                    </div>

                    @if (!empty($submission->missing_fields))
                        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            Campos pendentes: {{ implode(', ', $submission->missing_fields) }}
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-4">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Voltar para pagina inicial
                        </a>
                        <a href="{{ $submission->promotion_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md bg-pink-600 px-4 py-2 text-sm font-medium text-white hover:bg-pink-500">
                            Abrir site da promocao
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold text-gray-900">Logs da automacao</h3>

                        @if ($submission->logs->isEmpty())
                            <p class="mt-4 text-sm text-gray-600">Nenhum log registrado ainda.</p>
                        @else
                            <div class="mt-5 space-y-3">
                                @foreach ($submission->logs as $log)
                                    @php
                                        $levelMap = [
                                            'info' => 'border-blue-200 bg-blue-50 text-blue-900',
                                            'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
                                            'error' => 'border-red-200 bg-red-50 text-red-900',
                                        ];
                                        $levelClass = $levelMap[$log->level] ?? 'border-slate-200 bg-slate-50 text-slate-900';
                                    @endphp

                                    <div class="rounded-lg border p-3 {{ $levelClass }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide">{{ $log->level }}</span>
                                            <span class="text-xs">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                                        </div>
                                        <p class="mt-2 text-sm">{{ $log->message }}</p>
                                        @if (!empty($log->context))
                                            <pre class="mt-3 max-h-56 overflow-auto rounded bg-black/90 p-3 text-xs text-green-200">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold text-gray-900">Metadata tecnico</h3>

                        @if ($metadataJson)
                            <pre class="mt-4 max-h-[34rem] overflow-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100">{{ $metadataJson }}</pre>
                        @else
                            <p class="mt-4 text-sm text-gray-600">Metadata indisponivel para esta execucao.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
