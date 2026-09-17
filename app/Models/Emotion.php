<?php

namespace App\Models;

use App\Models\Record;
use Illuminate\Database\Eloquent\Model;

class Emotion extends Model
{
    protected $fillable = [
        'name',
        'color'
    ];

    public function records() {
        return $this->belongsToMany(Record::class);
    }
}
