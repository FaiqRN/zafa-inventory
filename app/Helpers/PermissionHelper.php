<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

if (!function_exists('authenticatedUserFromIdentifier')) {
    /**
     * Resolve authenticated user from auth identifier.
     *
     * @return User|null
     */
    function authenticatedUserFromIdentifier(): ?User
    {
        static $resolved = false;
        static $resolvedUser = null;

        if ($resolved) {
            return $resolvedUser;
        }

        $resolved = true;
        $authIdentifier = Auth::id();

        if ($authIdentifier === null) {
            return null;
        }

        return $resolvedUser = User::query()
            ->where(User::FIELD_USERNAME, (string) $authIdentifier)
            ->first();
    }
}

if (!function_exists('canAccess')) {
    /**
     * Check if authenticated user has permission
     *
     * @param string $permission
     * @return bool
     */
    function canAccess(string $permission): bool
    {
        /** @var User|null $user */
        $user = authenticatedUserFromIdentifier();
        return $user && $user->can($permission);
    }
}

if (!function_exists('hasRole')) {
    /**
     * Check if authenticated user has role
     *
     * @param string|array $role
     * @return bool
     */
    function hasRole($role): bool
    {
        /** @var User|null $user */
        $user = authenticatedUserFromIdentifier();
        return $user && $user->hasRole($role);
    }
}

if (!function_exists('hasAnyRole')) {
    /**
     * Check if authenticated user has any of the given roles
     *
     * @param array $roles
     * @return bool
     */
    function hasAnyRole(array $roles): bool
    {
        /** @var User|null $user */
        $user = authenticatedUserFromIdentifier();
        return $user && $user->hasAnyRole($roles);
    }
}

if (!function_exists('hasAllRoles')) {
    /**
     * Check if authenticated user has all of the given roles
     *
     * @param array $roles
     * @return bool
     */
    function hasAllRoles(array $roles): bool
    {
        /** @var User|null $user */
        $user = authenticatedUserFromIdentifier();
        return $user && $user->hasAllRoles($roles);
    }
}

if (!function_exists('userRoles')) {
    /**
     * Get authenticated user's roles
     *
     * @return Collection
     */
    function userRoles(): Collection
    {
        /** @var User|null $user */
        $user = authenticatedUserFromIdentifier();
        return $user ? $user->roles : collect();
    }
}

if (!function_exists('userPermissions')) {
    /**
     * Get authenticated user's permissions
     *
     * @return Collection
     */
    function userPermissions(): Collection
    {
        /** @var User|null $user */
        $user = authenticatedUserFromIdentifier();
        return $user ? $user->getAllPermissions() : collect();
    }
}
