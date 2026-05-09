<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Seller extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'balance',
        'commission_rate',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'commission_rate' => 'decimal:2',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, Product::class);
    }

    public function role(): string
    {
        return 'seller';
    }

    public function grossSales(): float
    {
        return (float) $this->orders()
            ->where('status', Order::STATUS_COMPLETED)
            ->sum('total_price');
    }

    public function commissionAmount(?float $grossSales = null): float
    {
        $grossSales ??= $this->grossSales();

        return round($grossSales * (((float) $this->commission_rate) / 100), 2);
    }

    public function netEarnings(?float $grossSales = null): float
    {
        $grossSales ??= $this->grossSales();

        return round($grossSales - $this->commissionAmount($grossSales), 2);
    }

    public function refreshBalance(): void
    {
        $this->forceFill([
            'balance' => $this->netEarnings(),
        ])->save();
    }
}