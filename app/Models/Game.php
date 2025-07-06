<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'cover_image_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the rulebooks for the game.
     */
    public function rulebooks(): HasMany
    {
        return $this->hasMany(Rulebook::class);
    }

    /**
     * Get the conversations for the game.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get ready rulebooks for the game.
     */
    public function readyRulebooks(): HasMany
    {
        return $this->hasMany(Rulebook::class)->where('status', 'ready');
    }

    /**
     * Get rulebooks for a specific language.
     */
    public function rulebooksForLanguage(string $language): HasMany
    {
        return $this->hasMany(Rulebook::class)->where('language', $language);
    }
}
