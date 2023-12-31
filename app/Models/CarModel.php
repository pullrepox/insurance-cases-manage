<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarModel extends Model
{
    use HasFactory;

    protected $fillable = ['car_make_id', 'model_name'];

    /**
     * Car Make
     * @return BelongsTo
     */
    public function carMake(): BelongsTo
    {
        return $this->belongsTo(CarMake::class);
    }

    /**
     * Insurance Cases
     *
     * @return HasMany
     */
    public function insuranceCases(): HasMany
    {
        return $this->hasMany(InsuranceCase::class);
    }
}
