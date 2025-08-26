<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoCashTempoDetail extends Model
{
    use HasFactory;
    protected $table = 'belidt';
    protected $primaryKey = 'belidtpk';
    protected $guarded = [];
    public $timestamps = false;
}
