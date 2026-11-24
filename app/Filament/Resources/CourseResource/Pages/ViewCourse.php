<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Models\CourseLesson;
use Filament\Resources\Pages\ViewRecord;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;
    protected static string $view = 'filament.resources.course-resource.pages.view-course';

    public ?int $activeLessonId = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record->load(['modules.lessons']);

        $firstLesson = $this->record->modules->flatMap->lessons->first();
        if ($firstLesson) {
            $this->activeLessonId = $firstLesson->id;
        }
    }

    public function selectLesson(int $lessonId): void
    {
        $this->activeLessonId = $lessonId;
    }

    public function getActiveLessonProperty(): ?CourseLesson
    {
        if (!$this->activeLessonId) {
            return null;
        }

        return $this->record->modules
            ->flatMap
            ->lessons
            ->firstWhere('id', $this->activeLessonId);
    }
}
