<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $primaryKey = "idrole";
    protected $table = "roles";

    public $timestamps = false;

    protected $fillable = [
        'idrole', 'name', 'state'
    ];
}
