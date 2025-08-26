<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoice';
    protected $guarded = [''];
    protected $primaryKey = 'invpk';
    public $timestamps = false;

    // public function invoice()
    // {
    //     return $this->hasOne(Invoice::class, 'spbpk', 'spbpk'); // Relasi dengan kolom spbpk
    // }
}
