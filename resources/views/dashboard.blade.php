<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pagina inicial') }}
        </h2>
    </x-slot>

    @php
        $missingFields = array_values(array_unique(array_merge(
            $missingProfileFields ?? [],
            session('missing_profile_fields', []),
        )));
        $showAttentionModal = $missingFields !== [] || !empty($attentionSubmission);
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($missingFields !== [])
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Complete seus dados antes de iniciar novos cadastros.</p>
                    <p class="mt-2">Campos obrigatorios faltando: {{ implode(', ', $missingFields) }}.</p>
                    <a href="{{ route('personal-data.edit') }}" class="mt-3 inline-flex text-sm font-medium text-amber-700 underline">
                        Ir para dados pessoais
                    </a>
                </div>
            @endif

            @if ($showAttentionModal)
                <div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" style="display: none;">
                    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900">Acao necessaria</h3>

                        @if ($missingFields !== [])
                            <p class="mt-3 text-sm text-gray-700">
                                Faltam informacoes no seu cadastro: {{ implode(', ', $missingFields) }}.
                            </p>
                            <a href="{{ route('personal-data.edit') }}" class="mt-4 inline-flex text-sm font-medium text-pink-700 underline">
                                Completar dados pessoais
                            </a>
                        @elseif (($attentionSubmission?->status) === 'captcha_required')
                            <p class="mt-3 text-sm text-gray-700">
                                Captcha detectado na promocao <span class="font-medium">{{ $attentionSubmission->promotion_url }}</span>.
                                Abra o site e finalize manualmente.
                            </p>
                        @elseif (($attentionSubmission?->status) === 'needs_info')
                            <p class="mt-3 text-sm text-gray-700">
                                O cadastro precisa de dados adicionais: {{ implode(', ', $attentionSubmission->missing_fields ?? []) }}.
                            </p>
                            <a href="{{ route('personal-data.edit') }}" class="mt-4 inline-flex text-sm font-medium text-pink-700 underline">
                                Atualizar dados pessoais
                            </a>
                        @endif

                        <div class="mt-6 flex justify-end">
                            <button @click="open = false" type="button" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900">Cadastrar promocao</h3>
                    <p class="mt-2 text-sm text-gray-600">Cole o link da promocao e envie para processamento.</p>

                    <form method="POST" action="{{ route('promotion-submissions.store') }}" class="mt-6 space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="promotion_url" :value="__('Link da promocao')" />
                            <x-text-input id="promotion_url" name="promotion_url" type="url" class="mt-1 block w-full" :value="old('promotion_url')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('promotion_url')" />
                        </div>

                        <x-primary-button>
                            {{ __('Cadastrar promocao') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900">Ultimos cadastros</h3>

                    @if ($submissions->isEmpty())
                        <p class="mt-4 text-sm text-gray-600">Nenhuma promocao cadastrada ainda.</p>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700">URL</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Status</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Detalhes</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Data</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Acoes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($submissions as $submission)
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
                                        @endphp

                                        <tr>
                                            <td class="px-4 py-3 align-top">
                                                <a href="{{ $submission->promotion_url }}" target="_blank" rel="noopener noreferrer" class="text-pink-700 underline break-all">
                                                    {{ $submission->promotion_url }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusData['class'] }}">
                                                    {{ $statusData['label'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 align-top text-gray-700">
                                                <p>{{ $submission->result_message ?? 'Aguardando processamento.' }}</p>
                                                @if (!empty($submission->missing_fields))
                                                    <p class="mt-1 text-xs text-amber-700">
                                                        Faltou: {{ implode(', ', $submission->missing_fields) }}
                                                    </p>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 align-top text-gray-600">
                                                {{ $submission->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <a href="{{ route('promotion-submissions.show', $submission) }}" class="inline-flex text-sm font-medium text-pink-700 underline">
                                                    Ver logs
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
