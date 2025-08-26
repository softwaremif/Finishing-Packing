<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listdb extends Model
{
    use HasFactory;

    protected $table = 'listdb';
    protected $primaryKey = 'listdb';
    public $timestamps = false;
    protected $guarded = [''];
}
