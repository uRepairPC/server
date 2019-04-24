<?php

namespace App;

use App\Traits\ModelHasDefaultTrait;
use Illuminate\Database\Eloquent\Model;

class RequestStatus extends Model
{
    use ModelHasDefaultTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'color',
        'description',
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
}
