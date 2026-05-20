<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = ['voyage_id', 'user_id', 'autorisation_parent'];
}
