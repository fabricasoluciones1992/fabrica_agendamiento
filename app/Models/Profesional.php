<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Profesional extends Model
{
    use HasFactory;

    protected $primaryKey = 'prof_id';

    protected $fillable = [
        'prof_name',
        'prof_status'
    ];

    public $timestamps = false;



}
