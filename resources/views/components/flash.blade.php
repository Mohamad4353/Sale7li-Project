@php($container = ($shell ?? 'app') === 'marketing' ? 'container-x' : 'container-app')
@php($formLevelKeys = ['payment', 'quotation', 'refund', 'worker_id'])
@php($formLevel = collect($formLevelKeys)->flatMap(fn ($k) => $errors->get($k)))
@php($hasFieldErrors = collect($errors->keys())->reject(fn ($k) => in_array($k, $formLevelKeys, true))->isNotEmpty())
@if (session('success') || session('status') || $errors->any())
    <div class="{{ $container }} no-print space-y-3 pt-5">
        @if (session('success') || session('status'))
            <x-alert tone="success" dismissible>{{ session('success') ?? session('status') }}</x-alert>
        @endif
        @if ($errors->any())
            {{-- Field errors render beside their fields (see <x-field>). The banner
                 only carries form-level errors that have no field of their own,
                 plus one concise line pointing at any highlighted fields. --}}
            <x-alert tone="danger">
                @foreach ($formLevel as $message)
                    <p @class(['mt-1' => ! $loop->first])>{{ $message }}</p>
                @endforeach
                @if ($hasFieldErrors)
                    <p @class(['mt-1' => $formLevel->isNotEmpty()])>{{ __('messages.fix_highlighted') }}</p>
                @endif
            </x-alert>
        @endif
    </div>
@endif
