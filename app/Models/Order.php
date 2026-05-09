<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'product_id',
        'customer_name',
        'email',
        'phone',
        'quantity',
        'notes',
        'locale',
        'unit_price',
        'total_price',
        'currency',
        'currency_position',
        'price_decimals',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function formattedAmount(float|string|null $amount = null): string
    {
        $amount ??= $this->total_price;
        $value = number_format((float) $amount, (int) $this->price_decimals);

        return $this->currency_position === 'suffix'
            ? $value.' '.$this->currency
            : $this->currency.$value;
    }

    public function formattedTotal(): string
    {
        return $this->formattedAmount($this->total_price);
    }
}