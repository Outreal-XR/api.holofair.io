<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Thanks for signing up! Click on the button below to navigate to our Portal.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">

        <div>
            <x-primary-button>
                <a href="{{ env('FRONT_URL') . '?verified=1' }}">
                    {{ __('Login') }}
                </a>
            </x-primary-button>
        </div>

    </div>
</x-guest-layout>