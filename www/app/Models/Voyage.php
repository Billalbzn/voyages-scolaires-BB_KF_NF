<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voyage extends Model
{
    protected $fillable = ['destination', 'date_depart', 'date_retour', 'places_max', 'user_id'];

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
