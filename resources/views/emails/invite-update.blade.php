<x-guest-layout>
    <h1 class="text-2xl font-bold mb-4 text-center">
        {{ __('Invite update to collaborate in a Metaverse') }}
    </h1>

    <p class="mb-4">
        Your invite role to collaborate in {{ $metaverseName }} has been updated by {{ $inviterName }} to {{ $role }}.
    </p>

    <p class="mb-4">
        @if($role === 'viewer')
        {{ __('To view the metaverse, click the button below.') }}
        @else
        {{ __('To accept the invitation, click the button below.') }}
        @endif
    </p>

    <x-primary-button class="mt-4 mb-4">
        <a href="{{ $url }}">
            {{ __('Accept Invitation') }}
        </a>
    </x-primary-button>


    <p class="mb-4">
        {{ __('Regards,') }}
    </p>

    <p>
        {{ __('HoloFair Team') }}
    </p>

</x-guest-layout>