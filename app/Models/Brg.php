<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brg extends Model
{
    use HasFactory;

    protected $table = 'brg';
    protected $primaryKey = 'brgpk';
    public $timestamps = false;
    protected $guarded = [''];
}
