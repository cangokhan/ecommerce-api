<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XmlSource extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'url',
        'is_active',
        'last_imported_at',
        'last_imported_count',
        'last_error',
        'import_interval_hours',
        'preferred_import_time',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_imported_at' => 'datetime',
            'import_interval_hours' => 'integer',
        ];
    }

    /**
     * Check if source should be imported now
     */
    public function shouldImport(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_imported_at) {
            return true; // Never imported
        }

        $hoursSinceLastImport = now()->diffInHours($this->last_imported_at);
        return $hoursSinceLastImport >= $this->import_interval_hours;
    }

    /**
     * Mark as imported
     */
    public function markAsImported(int $count): void
    {
        $this->update([
            'last_imported_at' => now(),
            'last_imported_count' => $count,
            'last_error' => null,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'last_error' => $error,
        ]);
    }
}

