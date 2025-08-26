<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipto extends Model
{
    use HasFactory;

    protected $table = 'shipto';
    protected $primaryKey = 'shiptopk';
    public $timestamps = false;
    protected $guarded = [''];

}
