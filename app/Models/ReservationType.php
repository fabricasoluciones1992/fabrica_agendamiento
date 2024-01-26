<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationType extends Model
{
    use HasFactory;
    
    protected $primaryKey ='res_typ_id';

    protected $fillable = [
      'res_typ_name'
    ];

    public $timestamps = false;
}
