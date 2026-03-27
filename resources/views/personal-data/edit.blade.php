<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dados pessoais') }}
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

                    <form method="POST" action="{{ route('personal-data.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="full_name" :value="__('Nome completo')" />
                                <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name', $profile->full_name)" />
                                <x-input-error class="mt-2" :messages="$errors->get('full_name')" />
                            </div>

                            <div>
                                <x-input-label for="cpf" :value="__('CPF')" />
                                <x-text-input id="cpf" name="cpf" type="text" class="mt-1 block w-full" :value="old('cpf', $profile->cpf)" />
                                <x-input-error class="mt-2" :messages="$errors->get('cpf')" />
                            </div>

                            <div>
                                <x-input-label for="rg" :value="__('RG')" />
                                <x-text-input id="rg" name="rg" type="text" class="mt-1 block w-full" :value="old('rg', $profile->rg)" />
                                <x-input-error class="mt-2" :messages="$errors->get('rg')" />
                            </div>

                            <div>
                                <x-input-label for="birth_date" :value="__('Data de nascimento')" />
                                <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', optional($profile->birth_date)->format('Y-m-d'))" />
                                <x-input-error class="mt-2" :messages="$errors->get('birth_date')" />
                            </div>

                            <div>
                                <x-input-label for="phone" :value="__('Telefone')" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $profile->phone)" />
                                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                            </div>

                            <div>
                                <x-input-label for="whatsapp" :value="__('Whatsapp')" />
                                <x-text-input id="whatsapp" name="whatsapp" type="text" class="mt-1 block w-full" :value="old('whatsapp', $profile->whatsapp)" />
                                <x-input-error class="mt-2" :messages="$errors->get('whatsapp')" />
                            </div>

                            <div>
                                <x-input-label for="instagram" :value="__('Instagram')" />
                                <x-text-input id="instagram" name="instagram" type="text" class="mt-1 block w-full" :value="old('instagram', $profile->instagram)" />
                                <x-input-error class="mt-2" :messages="$errors->get('instagram')" />
                            </div>

                            <div>
                                <x-input-label for="zip_code" :value="__('CEP')" />
                                <x-text-input id="zip_code" name="zip_code" type="text" class="mt-1 block w-full" :value="old('zip_code', $profile->zip_code)" />
                                <x-input-error class="mt-2" :messages="$errors->get('zip_code')" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="street" :value="__('Rua')" />
                                <x-text-input id="street" name="street" type="text" class="mt-1 block w-full" :value="old('street', $profile->street)" />
                                <x-input-error class="mt-2" :messages="$errors->get('street')" />
                            </div>

                            <div>
                                <x-input-label for="number" :value="__('Numero')" />
                                <x-text-input id="number" name="number" type="text" class="mt-1 block w-full" :value="old('number', $profile->number)" />
                                <x-input-error class="mt-2" :messages="$errors->get('number')" />
                            </div>

                            <div>
                                <x-input-label for="complement" :value="__('Complemento')" />
                                <x-text-input id="complement" name="complement" type="text" class="mt-1 block w-full" :value="old('complement', $profile->complement)" />
                                <x-input-error class="mt-2" :messages="$errors->get('complement')" />
                            </div>

                            <div>
                                <x-input-label for="neighborhood" :value="__('Bairro')" />
                                <x-text-input id="neighborhood" name="neighborhood" type="text" class="mt-1 block w-full" :value="old('neighborhood', $profile->neighborhood)" />
                                <x-input-error class="mt-2" :messages="$errors->get('neighborhood')" />
                            </div>

                            <div>
                                <x-input-label for="city" :value="__('Cidade')" />
                                <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $profile->city)" />
                                <x-input-error class="mt-2" :messages="$errors->get('city')" />
                            </div>

                            <div>
                                <x-input-label for="state" :value="__('UF')" />
                                <x-text-input id="state" name="state" type="text" class="mt-1 block w-full" :value="old('state', $profile->state)" maxlength="2" />
                                <x-input-error class="mt-2" :messages="$errors->get('state')" />
                            </div>

                            <div>
                                <x-input-label for="country" :value="__('Pais')" />
                                <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="old('country', $profile->country)" />
                                <x-input-error class="mt-2" :messages="$errors->get('country')" />
                            </div>
                        </div>

                        <x-primary-button>
                            {{ __('Salvar dados') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
