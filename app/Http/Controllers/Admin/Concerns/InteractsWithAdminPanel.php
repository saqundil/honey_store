<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait InteractsWithAdminPanel
{
    protected function panelRole(): string
    {
        return $this->isSellerPanel() ? 'seller' : 'admin';
    }

    protected function isSellerPanel(): bool
    {
        return Auth::guard('seller')->check();
    }

    protected function currentSeller(): ?Seller
    {
        /** @var Seller|null $seller */
        $seller = Auth::guard('seller')->user();

        return $seller;
    }

    protected function currentAdmin(): ?User
    {
        /** @var User|null $admin */
        $admin = Auth::guard('web')->user();

        return $admin;
    }

    protected function scopeProducts(Builder $query): Builder
    {
        if ($this->isSellerPanel()) {
            $query->where('seller_id', $this->currentSeller()?->id);
        }

        return $query;
    }

    protected function scopeOrders(Builder $query): Builder
    {
        if ($this->isSellerPanel()) {
            $sellerId = $this->currentSeller()?->id;

            $query->whereHas('product', function (Builder $productQuery) use ($sellerId): void {
                $productQuery->where('seller_id', $sellerId);
            });
        }

        return $query;
    }

    protected function ensureProductAccess(Product $product): void
    {
        if ($this->isSellerPanel() && $product->seller_id !== $this->currentSeller()?->id) {
            abort(403);
        }
    }

    protected function ensureOrderAccess(Order $order): void
    {
        if ($this->isSellerPanel() && $order->product?->seller_id !== $this->currentSeller()?->id) {
            abort(403);
        }
    }

    protected function selectedSellerId(?int $requestedSellerId = null): ?int
    {
        if ($this->isSellerPanel()) {
            return $this->currentSeller()?->id;
        }

        return $requestedSellerId;
    }
}