<?php

namespace App\Traits;

use App\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait ModelHasPermissions
{
    /**
     * @var int seconds
     * @default 3 days
     */
    protected $permissionCacheTime = 60 * 60 * 24 * 3;

    /**
     * @var string
     */
    protected $permissionCacheKey = 'permissions';

    /**
     * @var Collection
     */
    private $_permissionNames;

    /**
     * @param  {string|array}  $name
     * @return array
     */
    public function assignRolesByName($names): array
    {
        $names = is_array($names) ? $names : [$names];

        $query = Role::query();
        foreach ($names as $name) {
            $query->orWhere('name', $name);
        }
        $roles = $query->get();

        return $this->roles()->sync($roles->pluck('id'));
    }

    /**
     * @param  {string|array}  $ids
     * @return array
     */
    public function assignRolesById($ids): array
    {
        $ids = is_array($ids) ? $ids : [$ids];

        return $this->roles()->sync($ids);
    }

    /**
     * Return all the permissions the model has via roles.
     *
     * @return Collection
     */
    public function getAllPerm(): Collection
    {
        $key = $this->permissionCacheKey.'.'.$this->getTable().'.'.$this->id;

        return Cache::remember($key, $this->permissionCacheTime, function () {
            $this->loadMissing(['roles', 'roles.permissions']);

            return $this->roles->flatMap(function ($role) {
                return $role->permissions;
            })->sort()->values();
        });
    }

    /**
     * Get names of permissions.
     * @return array
     */
    public function getAllPermNames(): array
    {
        if (! $this->_permissionNames) {
            $this->_permissionNames = $this->getAllPerm()
                ->pluck('name')
                ->toArray();
        }

        return $this->_permissionNames;
    }

    /**
     * @param  {array|string}  $permissions
     * @return bool
     */
    public function perm($permissions): bool
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $userPermissions = $this->getAllPermNames();

        foreach ($permissions as $permission) {
            // Check boolean
            if (in_array($permission, ['1', 1, true], true)) {
                return true;
            }

            if (in_array($permission, $userPermissions)) {
                return true;
            }
        }

        return false;
    }
}
