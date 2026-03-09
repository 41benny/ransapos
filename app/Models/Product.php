<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'pos_user_ids',
        'category_id',
        'description',
        'image_path',
        'thumbnail_path',
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
        'pos_user_ids' => 'array',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'image_url',
        'thumbnail_url',
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
     * Relasi ke product costs (avg cost per outlet)
     */
    public function costs(): HasMany
    {
        return $this->hasMany(ProductCost::class);
    }

    /**
     * Relasi ke resep bundle (aktif maupun tidak).
     * Bundle disimpan sebagai source_type=bundle agar terpisah dari BOM produksi.
     */
    public function bomHeader(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BomHeader::class, 'product_id')
            ->where('source_type', 'bundle');
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
            ->map(fn($id) => (int) $id)
            ->all();

        return in_array((int) $outletId, $allowedOutletIds, true);
    }

    /**
     * Get price by level and outlet
     * Supports both legacy format (numeric) and new format (object with default and outlets)
     * 
     * @param string $priceLevel  Level harga (regular, gofood, dll)
     * @param int|null $outletId  ID outlet, jika null maka ambil default
     * @return float
     */
    public function getPriceByLevelAndOutlet(string $priceLevel, ?int $outletId = null): float
    {
        $requestedLevel = trim($priceLevel);
        $normalizedLevel = strtolower($requestedLevel);
        $priceMap = $this->price_levels ?? [];

        $resolvedLevel = $this->resolvePriceLevelKey($requestedLevel, $priceMap);

        if ($resolvedLevel === null) {
            return (float) $this->selling_price;
        }

        $levelData = $priceMap[$resolvedLevel];

        // Backward compatible: jika masih format lama (number)
        if (is_numeric($levelData)) {
            return (float) $levelData;
        }

        // Format baru (object dengan default dan outlets)
        if (is_array($levelData)) {
            // Jika ada outlet_id dan ada harga khusus untuk outlet tersebut
            if ($outletId && isset($levelData['outlets'][(string) $outletId])) {
                $outletPrice = $levelData['outlets'][(string) $outletId];
                if (is_numeric($outletPrice) && (float) $outletPrice > 0) {
                    return (float) $outletPrice;
                }
            }

            // Fallback ke default
            if (isset($levelData['default']) && is_numeric($levelData['default'])) {
                return (float) $levelData['default'];
            }
        }

        return (float) $this->selling_price;
    }

    /**
     * Resolve price level key without assuming the stored JSON key casing.
     *
     * Existing data may contain uppercase codes such as MEMBER_2 while newer
     * lookups may pass lowercase or mixed-case values.
     *
     * @param array<string, mixed> $priceMap
     */
    private function resolvePriceLevelKey(string $requestedLevel, array $priceMap): ?string
    {
        if (array_key_exists($requestedLevel, $priceMap)) {
            return $requestedLevel;
        }

        $lowerRequestedLevel = strtolower(trim($requestedLevel));

        foreach (array_keys($priceMap) as $storedLevel) {
            if (strtolower((string) $storedLevel) === $lowerRequestedLevel) {
                return (string) $storedLevel;
            }
        }

        return null;
    }

    /**
     * Get price by level (legacy method, maintains backward compatibility)
     * 
     * @param string $priceLevel
     * @return float
     */
    public function getPriceByLevel(string $priceLevel): float
    {
        return $this->getPriceByLevelAndOutlet($priceLevel, null);
    }

    public function isAvailableForUser(?int $userId): bool
    {
        if ($this->is_available_all_users || !$userId) {
            return true;
        }

        $allowedUserIds = collect($this->pos_user_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->all();

        return in_array((int) $userId, $allowedUserIds, true);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return Storage::url($this->image_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }

        return Storage::url($this->thumbnail_path);
    }
}
