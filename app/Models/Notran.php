<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notran extends Model
{
    use HasFactory;
    protected $table = 'notran';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $guarded = [];
    public $timestamps = false;
}
