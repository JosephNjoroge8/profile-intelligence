<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    // UUID primary key — not auto-incrementing
    public $incrementing = false;
    public $timestamps   = false;    // we own created_at, no updated_at
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'name',
        'gender',
        'gender_probability',
        'sample_size',
        'age',
        'age_group',
        'country_id',
        'country_probability',
        'created_at',
    ];

    protected $casts = [
        'gender_probability'  => 'float',
        'sample_size'         => 'integer',
        'age'                 => 'integer',
        'country_probability' => 'float',
        'created_at'          => 'datetime',
    ];

    /**
     * Full representation — used for POST (201), GET /{id}, and idempotency response.
     * Includes all fields including probabilities and sample_size.
     */
    public function toFullArray(): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'gender'              => $this->gender,
            'gender_probability'  => $this->gender_probability,
            'sample_size'         => $this->sample_size,
            'age'                 => $this->age,
            'age_group'           => $this->age_group,
            'country_id'          => $this->country_id,
            'country_probability' => $this->country_probability,
            'created_at'          => $this->created_at->toISOString(),
        ];
    }

    /**
     * Slim representation — used for GET /api/profiles list.
     * Omits probabilities and sample_size for a leaner payload.
     */
    public function toListArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'gender'     => $this->gender,
            'age'        => $this->age,
            'age_group'  => $this->age_group,
            'country_id' => $this->country_id,
        ];
    }
}
