<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Shops extends Model
{
    protected $fillable=['shop_category_id','shop_name','shop_img','shop_rating','brand','on_time','fengniao','bao','piao','start_send','send_cost','notice','discount','status'];
}
