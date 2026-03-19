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
  ];

  public function stats()
  {
    return $this->hasMany(Stat::class);
  }
}
