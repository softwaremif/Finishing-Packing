<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dbnotedt extends Model
{
    use HasFactory;

    protected $table = 'dbnotedt';
    protected $primaryKey = 'dbnotedtpk';
    public $timestamps = false;
    protected $guarded = [''];
}
