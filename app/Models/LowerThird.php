<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LowerThird extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'logo',
        'duration',
        'is_live',
        'shown_at',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'is_live' => 'boolean',
            'shown_at' => 'datetime',
        ];
    }

    /**
     * Lower-third yang sedang tayang; yang durasinya habis dianggap sudah turun.
     */
    public static function current(): ?self
    {
        $lowerThird = self::where('is_live', true)->latest('shown_at')->first();

        if ($lowerThird && $lowerThird->remainingSeconds() === 0) {
            return null;
        }

        return $lowerThird;
    }

    /**
     * Sisa detik tayang; null berarti tampil sampai disembunyikan manual.
     */
    public function remainingSeconds(): ?int
    {
        if ($this->duration === 0 || ! $this->shown_at) {
            return null;
        }

        return max(0, $this->duration - (int) $this->shown_at->diffInSeconds(now()));
    }

    /**
     * @return array{id: int, title: string, subtitle: string, logo: string, duration: int, remaining: int|null, shownAt: string|null}
     */
    public function toOverlayArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => (string) $this->subtitle,
            'logo' => (string) $this->logo,
            'duration' => $this->duration,
            'remaining' => $this->remainingSeconds(),
            'shownAt' => $this->shown_at?->toIso8601String(),
        ];
    }
}
