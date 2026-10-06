<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'id',
                'role',
                'status',
                'joined_at',
            ])
            ->withTimestamps();
    }

	public function clients(): HasMany
	{
	    return $this->hasMany(Client::class);
	}

	public function products(): HasMany
	{
	    return $this->hasMany(Product::class);
	}

	public function fiscalProfile(): HasOne
	{
	    return $this->hasOne(
	        CompanyFiscalProfile::class
	    );
	}

	public function fiscalSequences(): HasMany
	{
	    return $this->hasMany(
	        FiscalSequence::class
	    );
	}
}
