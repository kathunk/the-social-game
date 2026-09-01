<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single-use Catacombian.com offer code in the impossible-bot bounty
 * pool. Loaded via `php artisan elephant:add-offer-codes`; claimed (at
 * most one per user) when a player beats the bot on impossible mode.
 */
class ElephantOfferCode extends Model
{
    protected $fillable = [
        'code',
        'claimed_by_user_id',
        'source_challenge_id',
        'claimed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    public function claimedBy()
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }
}
