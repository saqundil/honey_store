<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'paid_at',
        'payer_name',
        'image_path',
        'image_paths',
        'attachment_path',
        'attachment_name',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'image_paths' => 'array',
        ];
    }

    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 2);
    }

    public function imageUrl(): ?string
    {
        return $this->imageUrls()[0] ?? null;
    }

    public function imagePaths(): array
    {
        $paths = [];

        if (filled($this->image_path)) {
            $paths[] = (string) $this->image_path;
        }

        foreach ((array) ($this->image_paths ?? []) as $path) {
            if (filled($path)) {
                $paths[] = (string) $path;
            }
        }

        return array_values(array_unique($paths));
    }

    public function imageUrls(): array
    {
        return array_map(
            fn (string $path): string => asset('storage/'.ltrim($path, '/')),
            $this->imagePaths(),
        );
    }

    public function attachmentUrl(): ?string
    {
        if (blank($this->attachment_path)) {
            return null;
        }

        return asset('storage/'.ltrim((string) $this->attachment_path, '/'));
    }

    public function attachmentLabel(): ?string
    {
        if (blank($this->attachment_path)) {
            return null;
        }

        return $this->attachment_name ?: basename((string) $this->attachment_path);
    }
}