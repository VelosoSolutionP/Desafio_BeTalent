<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gateway extends Model
{
    protected $fillable = ['name', 'is_active', 'priority'];

    protected $casts = ['is_active' => 'boolean'];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
