<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document = formalité administrative rattachée à un voyage
 * (ex. passeport, attestation d'assurance).
 */
class Document extends Model
{
    protected $fillable = ['voyage_id', 'titre', 'chemin_fichier'];

    /** Le voyage auquel appartient ce document. */
    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}
