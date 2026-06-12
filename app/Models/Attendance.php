<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'check_in', 'check_out', 'status', 'note'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getWorkedHoursAttribute(): ?float
    {
        if (! $this->check_in || ! $this->check_out) {
            return null;
        }

        return round($this->check_in->diffInMinutes($this->check_out) / 60, 2);
    }
}
