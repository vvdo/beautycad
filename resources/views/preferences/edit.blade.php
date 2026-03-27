<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configuracoes') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->has('accept_terms'))
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            {{ $errors->first('accept_terms') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('preferences.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="checkbox" name="accept_terms" value="1" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" @checked(old('accept_terms', $preference->accept_terms))>
                                <span class="text-sm text-gray-700">Permito o uso dos meus dados para cadastro automatico em promocoes.</span>
                            </label>

                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="checkbox" name="auto_reject_cookies" value="1" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" @checked(old('auto_reject_cookies', $preference->auto_reject_cookies))>
                                <span class="text-sm text-gray-700">Tentar rejeitar cookies automaticamente quando houver essa opcao.</span>
                            </label>

                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="checkbox" name="pause_on_captcha" value="1" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" @checked(old('pause_on_captcha', $preference->pause_on_captcha))>
                                <span class="text-sm text-gray-700">Pausar fluxo quando aparecer captcha e aguardar acao manual.</span>
                            </label>

                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="checkbox" name="receive_newsletter" value="1" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" @checked(old('receive_newsletter', $preference->receive_newsletter))>
                                <span class="text-sm text-gray-700">Aceitar recebimento de newsletter quando essa opcao estiver disponivel.</span>
                            </label>

                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="checkbox" name="allow_marketing_emails" value="1" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" @checked(old('allow_marketing_emails', $preference->allow_marketing_emails))>
                                <span class="text-sm text-gray-700">Aceitar comunicacoes promocionais por e-mail.</span>
                            </label>

                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="checkbox" name="allow_marketing_sms" value="1" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" @checked(old('allow_marketing_sms', $preference->allow_marketing_sms))>
                                <span class="text-sm text-gray-700">Aceitar comunicacoes promocionais por SMS.</span>
                            </label>

                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="checkbox" name="allow_third_party_share" value="1" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" @checked(old('allow_third_party_share', $preference->allow_third_party_share))>
                                <span class="text-sm text-gray-700">Permitir compartilhamento com parceiros da promocao.</span>
                            </label>
                        </div>

                        <div>
                            <x-input-label for="preferred_contact_channel" :value="__('Canal preferencial de contato')" />
                            <select id="preferred_contact_channel" name="preferred_contact_channel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                                @php($selectedChannel = old('preferred_contact_channel', $preference->preferred_contact_channel))
                                <option value="email" @selected($selectedChannel === 'email')>E-mail</option>
                                <option value="whatsapp" @selected($selectedChannel === 'whatsapp')>Whatsapp</option>
                                <option value="sms" @selected($selectedChannel === 'sms')>SMS</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('preferred_contact_channel')" />
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Observacoes adicionais')" />
                            <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">{{ old('notes', $preference->notes) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                        </div>

                        <x-primary-button>
                            {{ __('Salvar configuracoes') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
