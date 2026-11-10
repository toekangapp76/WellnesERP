<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinesCurrency extends Model
{
    use HasFactory;

    protected $table = 'busines_currency';

    protected $guarded = [];

    protected $casts = [
        'value' => 'float',
    ];
}
