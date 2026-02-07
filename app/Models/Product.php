<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'product_type',
        'is_sellable',
        'is_pos_available',
        'is_online_order_available',
        'is_available_all_outlets',
        'is_available_all_users',
        'pos_outlet_ids',
        'category_id',
        'description',
        'unit',
        'purchase_price',
        'selling_price',
        'price_levels',
        'min_stock',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'price_levels' => 'array',
        'min_stock' => 'integer',
        'is_sellable' => 'boolean',
        'is_pos_available' => 'boolean',
        'is_online_order_available' => 'boolean',
        'is_available_all_outlets' => 'boolean',
        'is_available_all_users' => 'boolean',
        'pos_outlet_ids' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Relasi ke creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke stocks
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Relasi ke stock mutations
     */
    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class);
    }

    /**
     * Relasi ke sale items
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Relasi ke purchase items
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Relasi ke BOM header (aktif maupun tidak)
     */
    public function bomHeader(): 
    \Illuminate\Database\Eloquent\Relations\HasOne {
        return $this->hasOne(BomHeader::class, 'product_id');
    }

    public function isAvailableForOutlet(?int $outletId): bool
    {
        if (!$this->is_active || !$this->is_sellable || !$this->is_pos_available) {
            return false;
        }

        if ($this->is_available_all_outlets || !$outletId) {
            return true;
        }

        $allowedOutletIds = collect($this->pos_outlet_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        return in_array((int) $outletId, $allowedOutletIds, true);
    }

    public function getPriceByLevel(string $priceLevel): float
    {
        $normalizedLevel = strtolower(trim($priceLevel));
        $priceMap = $this->price_levels ?? [];

        if (array_key_exists($normalizedLevel, $priceMap) && $priceMap[$normalizedLevel] !== null) {
            return (float) $priceMap[$normalizedLevel];
        }

        return (float) $this->selling_price;
    }
}
