<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'image',
        'sku',
        'price_value',
        'currency',
        'currency_position',
        'price_decimals',
        'sort_order',
        'is_active',
        'translations',
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'is_active' => 'boolean',
            'price_value' => 'decimal:2',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function translation(?string $locale = null): array
    {
        $translations = $this->translations ?? [];
        $locale ??= app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');

        if (isset($translations[$locale]) && is_array($translations[$locale])) {
            return $translations[$locale];
        }

        if (isset($translations[$fallbackLocale]) && is_array($translations[$fallbackLocale])) {
            return $translations[$fallbackLocale];
        }

        foreach ($translations as $translation) {
            if (is_array($translation)) {
                return $translation;
            }
        }

        return [];
    }

    public function localized(string $key, mixed $default = null, ?string $locale = null): mixed
    {
        return data_get($this->translation($locale), $key, $default);
    }

    public function formattedPrice(): string
    {
        $value = number_format((float) $this->price_value, (int) $this->price_decimals);

        return $this->currency_position === 'suffix'
            ? $value.' '.$this->currency
            : $this->currency.$value;
    }

    public function toLocalizedArray(?string $locale = null): array
    {
        $translation = $this->translation($locale);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'image' => $this->image,
            'sku' => $this->sku,
            'price' => $this->formattedPrice(),
            'price_value' => (float) $this->price_value,
            'currency' => $this->currency,
            'currency_position' => $this->currency_position,
            'price_decimals' => (int) $this->price_decimals,
            'name' => $translation['name'] ?? $this->slug,
            'excerpt' => $translation['excerpt'] ?? '',
            'description' => $translation['description'] ?? '',
            'origin' => $translation['origin'] ?? '',
            'texture' => $translation['texture'] ?? '',
            'size' => $translation['size'] ?? '',
            'category' => $translation['category'] ?? '',
            'tags' => array_values($translation['tags'] ?? []),
            'highlights' => array_values($translation['highlights'] ?? []),
            'gallery' => array_values($translation['gallery'] ?? []),
            'badge' => $translation['badge'] ?? null,
        ];
    }
}