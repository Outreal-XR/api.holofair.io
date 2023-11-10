<x-guest-layout>
    <h1 class="text-2xl font-bold mb-4 text-center">
        {{ __('Verify Your Account') }}
    </h1>

    <p class="">Click the button below to verify your account.</p>

    <x-primary-button class="mt-4 mb-4">
        <a href="{{ $url }}">{{ __('Verify Account')}}</a>
    </x-primary-button>

    <p class="mb-4">
        If you can not click the button above, copy and paste the URL below into your web browser.
    </p>


    <a class="underline" href="{{ $url }}">{{$url}}</a>
</x-guest-layout>