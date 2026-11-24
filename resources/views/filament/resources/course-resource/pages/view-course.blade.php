<x-filament-panels::page>
    @php
        $course = $this->record;
        $activeLesson = $this->activeLesson;
        $embedUrl = $activeLesson ? \App\Filament\Resources\CourseResource::getEmbedUrl($activeLesson->video_url) : null;
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left / Main Video Player & Lesson Info --}}
        <div class="lg:col-span-2 space-y-6">
            <x-filament::section>
                @if($activeLesson)
                    @if($embedUrl)
                        <div style="aspect-ratio: 16 / 9; width: 100%; background: #000; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
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
                    @else
                        <div class="aspect-video w-full rounded-xl bg-gray-100 dark:bg-gray-800 flex flex-col items-center justify-center p-6 text-center mb-4">
                            <x-filament::icon icon="heroicon-o-video-camera" class="h-12 w-12 text-gray-400 mb-2" />
                            <p class="text-sm text-gray-500">Video for this lesson will be available soon.</p>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                            {{ $activeLesson->title_en }}
                        </h2>
                        @if($activeLesson->duration_minutes)
                            <x-filament::badge color="primary" icon="heroicon-m-clock">
                                {{ $activeLesson->duration_minutes }} min
                            </x-filament::badge>
                        @endif
                    </div>

                    @if($activeLesson->description_en)
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                            {{ $activeLesson->description_en }}
                        </p>
                    @endif
                @else
                    <div class="py-12 text-center">
                        <x-filament::icon icon="heroicon-o-academic-cap" class="h-12 w-12 text-primary-500 mx-auto mb-3" />
                        <h3 class="text-lg font-bold text-gray-950 dark:text-white">{{ $course->title_en }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $course->description_en }}</p>
                        <p class="mt-4 text-xs text-gray-400">No lessons have been published to this course yet.</p>
                    </div>
                @endif
            </x-filament::section>

            {{-- Course Overview --}}
            <x-filament::section heading="About This Course" collapsible>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                    {{ $course->description_en ?: 'No description provided.' }}
                </p>
            </x-filament::section>
        </div>

        {{-- Right Sidebar: Modules & Lessons Curriculum --}}
        <div class="space-y-4">
            <x-filament::section heading="Course Curriculum" icon="heroicon-o-list-bullet">
                @if($course->modules->isEmpty())
                    <p class="text-sm text-gray-500">No modules added yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($course->modules as $moduleIndex => $module)
                            <div class="rounded-lg border border-gray-200 dark:border-white/10 overflow-hidden">
                                <div class="bg-gray-50 dark:bg-white/5 px-4 py-2.5 font-semibold text-sm text-gray-900 dark:text-white flex items-center justify-between">
                                    <span>Module {{ $moduleIndex + 1 }}: {{ $module->title_en }}</span>
                                    <span class="text-xs text-gray-400">{{ $module->lessons->count() }} lessons</span>
                                </div>
                                <div class="divide-y divide-gray-100 dark:divide-white/5">
                                    @forelse($module->lessons as $lesson)
                                        @php
                                            $isActive = $activeLesson && $activeLesson->id === $lesson->id;
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="selectLesson({{ $lesson->id }})"
                                            class="w-full text-left px-4 py-3 flex items-center justify-between gap-3 transition hover:bg-primary-50/60 dark:hover:bg-primary-500/10 {{ $isActive ? 'bg-primary-50 dark:bg-primary-500/15 font-semibold text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}"
                                        >
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <x-filament::icon
                                                    :icon="$isActive ? 'heroicon-m-play-circle' : 'heroicon-o-play-circle'"
                                                    class="h-5 w-5 shrink-0 {{ $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}"
                                                />
                                                <span class="text-sm truncate">{{ $lesson->title_en }}</span>
                                            </div>
                                            @if($lesson->duration_minutes)
                                                <span class="text-xs text-gray-400 shrink-0">{{ $lesson->duration_minutes }}m</span>
                                            @endif
                                        </button>
                                    @empty
                                        <p class="px-4 py-2.5 text-xs text-gray-400">No lessons in this module.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
