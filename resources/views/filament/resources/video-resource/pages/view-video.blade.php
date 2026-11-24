<x-filament-panels::page>
    @php
        $video = $this->record;
        $embedUrl = \App\Filament\Resources\VideoResource::getEmbedUrl($video->video_url);
    @endphp

    <x-filament::section>
        @if($embedUrl)
            <div style="aspect-ratio: 16 / 9; width: 100%; background: #000; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1.5rem;">
                @if(str_contains($embedUrl, 'youtube.com/embed') || str_contains($embedUrl, 'player.vimeo.com'))
                    <iframe
                        src="{{ $embedUrl }}"
                        style="width: 100%; height: 100%; border: 0;"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                @else
                    <video src="{{ $embedUrl }}" controls style="width: 100%; height: 100%;"></video>
                @endif
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-2xl font-bold text-gray-950 dark:text-white">{{ $video->title_en }}</h2>
            @if($video->duration_minutes)
                <x-filament::badge color="primary" icon="heroicon-m-clock">
                    {{ $video->duration_minutes }} minutes
                </x-filament::badge>
            @endif
        </div>

        @if($video->description_en)
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                {{ $video->description_en }}
            </p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
