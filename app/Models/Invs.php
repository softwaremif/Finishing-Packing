<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invs extends Model
{
    use HasFactory;

    protected $table = 'invs';
    protected $primaryKey = 'invspk';
    public $timestamps = false;
    protected $guarded = [''];
}
