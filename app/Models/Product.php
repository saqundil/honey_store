<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
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

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function scopeOwnedBySeller(Builder $query, int $sellerId): Builder
    {
        return $query->where('seller_id', $sellerId);
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
        return $this->formatAmount((float) $this->price_value);
    }

    public function formatAmount(float $value): string
    {
        $value = number_format($value, (int) $this->price_decimals);

        return $this->currency_position === 'suffix'
            ? $value.' '.$this->currency
            : $this->currency.$value;
    }

    public function imageUrl(): string
    {
        if (blank($this->image)) {
            return 'https://placehold.co/600x600?text=Honey';
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset(ltrim($this->image, '/'));
    }

    public function toLocalizedArray(?string $locale = null): array
    {
        $translation = $this->translation($locale);
        $compareAtPrice = filled($translation['compare_at_price_value'] ?? null)
            ? (float) $translation['compare_at_price_value']
            : null;

        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'slug' => $this->slug,
            'image' => $this->image,
            'hero_image' => $translation['hero_image'] ?? null,
            'image_url' => $this->imageUrl(),
            'sku' => $this->sku,
            'price' => $this->formattedPrice(),
            'price_value' => (float) $this->price_value,
            'compare_at_price' => $compareAtPrice ? $this->formatAmount($compareAtPrice) : null,
            'compare_at_price_value' => $compareAtPrice,
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