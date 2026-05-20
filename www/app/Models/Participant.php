<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    protected $fillable = ['voyage_id', 'user_id', 'autorisation_parent'];

    // Un participant correspond à un utilisateur
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Un participant appartient à un voyage
    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}
