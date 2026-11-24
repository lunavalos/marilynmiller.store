<x-filament-widgets::widget>
    <div class="space-y-8">
        {{-- Welcome & Plan Banner --}}
        <x-filament::section>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Welcome back, {{ $user->name }}! ✨
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($plan)
                            You are subscribed to the <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $plan->name_en }}</span> plan. Here is everything included in your membership.
                        @else
                            Explore your member dashboard and available training programs below.
                        @endif
                    </p>

                    @if($plan && !empty($plan->features))
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($plan->features as $feat)
                                <x-filament::badge color="primary" icon="heroicon-m-check-circle">
                                    {{ $feat['feature_en'] ?? '' }}
                                </x-filament::badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-filament::button tag="a" href="{{ \Jeffgreco13\FilamentBreezy\Pages\MyProfilePage::getUrl() }}" color="gray" icon="heroicon-m-credit-card">
                        My Plan & Profile
                    </x-filament::button>
                    @if(\App\Filament\Resources\CourseResource::canViewAny())
                        <x-filament::button tag="a" href="{{ \App\Filament\Resources\CourseResource::getUrl('index') }}" icon="heroicon-m-academic-cap">
                            Browse Courses
                        </x-filament::button>
                    @endif
                    @if(\App\Filament\Resources\LiveEventResource::canViewAny())
                        <x-filament::button tag="a" href="{{ \App\Filament\Resources\LiveEventResource::getUrl('index') }}" color="gray" icon="heroicon-m-video-camera">
                            Live Events
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>

        {{-- My Courses --}}
        @if(\App\Filament\Resources\CourseResource::canViewAny())
            <x-filament::section heading="My Courses" description="Structured training programs included in your plan" icon="heroicon-o-academic-cap">
                @if($courses->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 py-4">No courses are currently published for your plan yet.</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($courses as $course)
                            <div class="flex flex-col rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
                                @if($course->thumbnail_url)
                                    <img src="{{ asset('storage/' . $course->thumbnail_url) }}" alt="{{ $course->title_en }}" class="h-44 w-full object-cover" />
                                @else
                                    <div class="h-44 w-full bg-primary-500/10 flex items-center justify-center">
                                        <x-filament::icon icon="heroicon-o-academic-cap" class="h-12 w-12 text-primary-500" />
                                    </div>
                                @endif
                                <div class="p-4 flex-1 flex flex-col justify-between space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <x-filament::badge color="primary" size="sm">
                                                {{ $course->modules_count }} {{ \Illuminate\Support\Str::plural('Module', $course->modules_count) }}
                                            </x-filament::badge>
                                        </div>
                                        <h3 class="font-bold text-base text-gray-950 dark:text-white">{{ $course->title_en }}</h3>
                                        @if($course->description_en)
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $course->description_en }}</p>
                                        @endif
                                    </div>
                                    <div class="pt-2">
                                        <x-filament::button tag="a" href="{{ \App\Filament\Resources\CourseResource::getUrl('view', ['record' => $course]) }}" size="sm" icon="heroicon-m-play-circle" class="w-full">
                                            Start / Watch Course
                                        </x-filament::button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        @endif

        {{-- Upcoming Live Events & Agenda Sessions --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @if(\App\Filament\Resources\LiveEventResource::canViewAny())
                <x-filament::section heading="Live Events" description="Join interactive live coaching & classes" icon="heroicon-o-video-camera">
                    @if($liveEvents->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400 py-4">No upcoming live events scheduled right now.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($liveEvents as $event)
                                <div class="flex items-center justify-between gap-4 p-3 rounded-lg border border-gray-200 dark:border-white/10">
                                    <div>
                                        <h4 class="font-semibold text-sm text-gray-950 dark:text-white">{{ $event->title_en }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $event->start_time?->format('M d, Y · g:i A') }}
                                        </p>
                                    </div>
                                    @if($event->meeting_url)
                                        <x-filament::button tag="a" href="{{ $event->meeting_url }}" target="_blank" size="xs" color="success" icon="heroicon-m-arrow-top-right-on-square">
                                            Join Live
                                        </x-filament::button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-filament::section>
            @endif

            @if(\App\Filament\Resources\VideoResource::canViewAny() || $videos->isNotEmpty())
                <x-filament::section heading="On-Demand Videos" description="Workout videos included in your membership" icon="heroicon-o-play-circle">
                    @if($videos->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400 py-4">No on-demand videos published for your plan yet.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($videos as $video)
                                <div class="flex items-center justify-between gap-4 p-3 rounded-lg border border-gray-200 dark:border-white/10">
                                    <div>
                                        <h4 class="font-semibold text-sm text-gray-950 dark:text-white">{{ $video->title_en }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $video->duration_minutes ? $video->duration_minutes . ' mins' : 'On-demand video' }}
                                        </p>
                                    </div>
                                    <x-filament::button tag="a" href="{{ \App\Filament\Resources\VideoResource::getUrl('view', ['record' => $video]) }}" size="xs" icon="heroicon-m-play">
                                        Watch
                                    </x-filament::button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
