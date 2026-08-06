<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'employee_number',
        'gender',
        'date_of_birth',
        'national_id',
        'address',
        'date_joined',
        'qualification',
        'specialization',
        'employment_type',
        'salary',
        'profile_photo',
    ];

    protected $casts = [
        'gender'          => Gender::class,
        'employment_type' => EmploymentType::class,
        'date_of_birth'   => 'date',
        'date_joined'     => 'date',
        'salary'          => 'decimal:2',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        return asset('storage/' . $this->profile_photo);
    }
}
