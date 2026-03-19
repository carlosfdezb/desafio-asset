<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
  use HasFactory;

  protected $fillable = [
    'slug_id',
    'referer_host',
    'country',
    'clicked_at',
  ];

  protected $casts = [
    'clicked_at' => 'datetime',
  ];

  public function slug()
  {
    return $this->belongsTo(Slug::class);
  }
}
