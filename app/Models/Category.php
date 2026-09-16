<?php

namespace App\Models;

use App\Models\Record;
use Illuminate\Database\Eloquent\Model;

class Cateogry extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function records() {
        return $this->hasMany(Record::class);
    }
}
