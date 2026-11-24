@props(['title', 'description'])

<div class="pt-6 space-y-4 filament-breezy-grid-section">
    <div>
        <h3 class="text-lg font-semibold text-gray-950 dark:text-white filament-breezy-grid-title">
            {{ $title }}
        </h3>

        @if(filled($description))
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 filament-breezy-grid-description">
                {{ $description }}
            </p>
        @endif
    </div>

    <div>
        {{ $slot }}
    </div>
</div>

