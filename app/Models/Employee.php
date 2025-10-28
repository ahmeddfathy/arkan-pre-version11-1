<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSecureId;

class Employee extends Model
{
    use HasSecureId;

    protected $fillable = [
    'name'
  ];

  public function specialCases()
  {
    return $this->hasMany(SpecialCase::class);
  }
}
