<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'public_id',
        'firstname',
        'lastname',
        'email',
        'phone',
        'country',
        'title',
        'picture', 
        'city',
        'address',
        'pos',
        'driving',
        'nationality',
        'place',
        'birthdate'
    ];

}
