<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dbnote extends Model
{
    use HasFactory;

    protected $table = 'dbnote';
    protected $primaryKey = 'dbnotepk';
    public $timestamps = false;
    protected $guarded = [''];
}
