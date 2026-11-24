<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasResourcePermissions
{
    protected static function getPermissionSlug(): string
    {
        return str(static::getModelLabel())->plural()->slug('_')->toString();
    }

    public static function isSubscriber(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('subscribed') && !$user->hasAnyRole(['super_admin', 'admin']);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $slug = static::getPermissionSlug();
        return $user->can("view_any_{$slug}") || $user->can("view_{$slug}");
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $slug = static::getPermissionSlug();
        return $user->can("view_{$slug}") || $user->can("view_any_{$slug}");
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $slug = static::getPermissionSlug();
        return $user->can("create_{$slug}");
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $slug = static::getPermissionSlug();
        return $user->can("update_{$slug}");
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $slug = static::getPermissionSlug();
        return $user->can("delete_{$slug}");
    }

    public static function canDeleteAny(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $slug = static::getPermissionSlug();
        return $user->can("delete_any_{$slug}");
    }

    public static function getEmbedUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        // YouTube watch?v= or youtu.be/ or youtube.com/shorts/
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return $url;
    }
}
