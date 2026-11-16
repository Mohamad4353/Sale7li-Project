@php($other = app()->getLocale() === 'ar' ? 'en' : 'ar')
<form method="POST" action="{{ route('lang') }}" data-no-busy {{ $attributes }}>
    @csrf
    <input type="hidden" name="locale" value="{{ $other }}">
    <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
    <button type="submit" class="lang-switch" aria-label="{{ __('nav.switch_language') }}">
        {{ app()->getLocale() === 'ar' ? 'EN' : 'ع' }}
    </button>
</form>
