<?php

namespace App\Models;

use App\Enums\RecordVisibility;
use App\Models\Category;
use App\Models\Emotion;
use App\Models\Tier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{

    use HasFactory;

    protected $fillable = [
    'title',
    'description',
    'date',
    'image_path',
    'image_alt',
    'visibility',
    'category_id',
    'tier_id',
    'user_id',
];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function tiers() {
        return $this->belongsTo(Tier::class);
    }

    public function users() {
        return $this->belongsTo(User::class);
    }

    public function emotions() {
        return $this->belongsToMany(Emotion::class);
    }

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
