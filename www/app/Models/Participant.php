<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = ['voyage_id', 'user_id', 'autorisation_parent'];

    protected function casts(): array
    {
        return [
            'autorisation_parent' => 'boolean',
        ];
    }

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
