<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoCashTempo extends Model
{
    use HasFactory;
    protected $table = 'beli';
    protected $primaryKey = 'belipk';
    protected $guarded = [];
    public $timestamps = false;

    public function details()
    {
        return $this->hasMany(PoCashTempoDetail::class, 'belipk', 'belipk');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'userpk', 'userpk');
    }

    public function Supplier()
    {
        return $this->belongsTo(Supplier::class, 'suppk', 'suppk');
    }
}
