<?php

namespace App;

use App\Enums\Perm;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /** @var array */
    const ALLOW_COLUMNS_SEARCH = [
        'id',
        'name',
        'default',
        'updated_at',
        'created_at',
    ];

    /** @var array */
    const ALLOW_COLUMNS_SORT = [
        'id',
        'name',
        'default',
        'updated_at',
        'created_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'color',
        'default',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'default' => 'boolean',
    ];

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $permissionsList = (object) [];
        $permissionsActive = [];

        if (! empty($this->permissions)) {
            $permissions = $this->permissions->pluck('name')->toArray();

            foreach (Perm::getStructure() as $key => $arr) {
                $temp = [];
                foreach ($arr as $permission => $action) {
                    $active = in_array($permission, $permissions);

                    $temp[] = (object) [
                         'name' => $permission,
                         'action' => $action,
                         'active' => $active,
                     ];

                    if ($active) {
                        $permissionsActive[] = $permission;
                    }
                }
                $permissionsList->{$key} = $temp;
            }
        }

        $this->setAttribute('permissions_list', $permissionsList);
        $this->setAttribute('permissions_active', $permissionsActive);
        $this->makeHidden('permissions');

        return parent::toArray();
    }

    /* | -----------------------------------------------------------------------------------
     * | Relationships
     * | -----------------------------------------------------------------------------------
     */

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions()
    {
        return $this->hasMany(RolePermission::class);
    }
}
