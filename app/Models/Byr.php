<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Byr extends Model
{
    use HasFactory;

    protected $table = 'byr';
    protected $primaryKey = 'byrpk';
    public $timestamps = false;
    protected $guarded = [''];
}
