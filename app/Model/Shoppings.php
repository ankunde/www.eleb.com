<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Shoppings extends Model
{
    protected $fillable=['user_id','goods_id','amount'];
}
