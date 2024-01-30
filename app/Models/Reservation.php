<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $primaryKey = 'res_id';

    protected $fillable = [
      'res_date',
      'res_start',
      'res_end',
      'res_typ_id',
      'spa_id',
      'use_id'
    ];

    public $timestamps = false;
}
