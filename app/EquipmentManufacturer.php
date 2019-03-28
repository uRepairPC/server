<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EquipmentManufacturer extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'updated_at',
        'created_at',
    ];

    /* | ---------------------------------------------------------------
     * | Relationships
     * | ---------------------------------------------------------------
     */

    public function equipments()
    {
        return $this->hasMany(Equipment::class);
    }

    public function models()
    {
        return $this->hasMany(EquipmentModel::class);
    }
}
