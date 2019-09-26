<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $primaryKey = "iduser";

    public $timestamps = false;

    protected $fillable = [
        'idproject', 'name', 'image', 'startdate', 'state'
    ];
}
