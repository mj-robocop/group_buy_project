<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function parentRelation()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function childrenRelation()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
