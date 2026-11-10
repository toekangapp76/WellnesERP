<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    public function business()
    {
        return $this->hasOne(BusinesCurrency::class);
    }
}
