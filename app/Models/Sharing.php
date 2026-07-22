<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sharing extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function counseling()
    {
        return $this->hasOne(Counseling::class);
    }

    /**
     * Get the sharing's NLP analysis.
     */
    public function nlp(): MorphOne
    {
        return $this->morphOne(NlpAnalysis::class, 'nlpable');
    }
}
