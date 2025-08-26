<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spb extends Model
{
    use HasFactory;

    protected $table = 'spb';
    protected $guarded = [''];
    protected $primaryKey = 'spbpk';
    public $timestamps = false;

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'spbpk', 'spbpk'); // Relasi dengan kolom spbpk
    }
}
