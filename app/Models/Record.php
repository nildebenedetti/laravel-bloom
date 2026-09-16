<?php

namespace App\Models;

use App\Enums\RecordVisibility;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
            protected $fillable = [
            'title',
            'description',
            'date',
            'iamge_path',
            'iamge_alt',
            'visibility'
        ];
    // Automatically transforms raw database values into typed PHP objects
    // (e.g., Enums and Carbon dates).
    // Ensures strict type safety and seamless data conversion 
    // when reading or writing model attributes.
    protected function casts(): array
    {
    
        return[
            'visibility' => RecordVisibility::class,
            'date' => 'date', // carbon library provides parsing into carbon obj
        ];
    }
}
