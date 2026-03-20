<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slug extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'original_url',
    'slug',
    'api_key',
    'expires_at',
  ];

  protected $hidden = [
    'api_key',
  ];

  protected function casts(): array
  {
    return [
      'expires_at' => 'datetime',
    ];
  }

  public function isExpired(): bool
  {
    return $this->expires_at !== null && $this->expires_at->isPast();
  }

  public function stats()
  {
    return $this->hasMany(Stat::class);
  }
}
