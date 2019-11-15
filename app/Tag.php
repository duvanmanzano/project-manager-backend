<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    
    protected $primaryKey = "idtag";

    public $timestamps = false;

    
    protected $fillable = [
        'idtag', 'name', 'state', 'idproject', 'color'
    ];
}
