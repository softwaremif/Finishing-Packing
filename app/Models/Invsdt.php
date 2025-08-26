<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invsdt extends Model
{
    use HasFactory;

    protected $table = 'invsdt';
    protected $primaryKey = 'invsdtpk';
    public $timestamps = false;
    protected $guarded = [''];
}
