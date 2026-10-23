<?php

namespace App\Services\Packages;

use App\Models\CocktailMenuItem;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\PackagePriceTier;
use App\Models\Product;
use App\Services\Packages\Exceptions\GuestOutOfRangeException;
use App\Services\Packages\Exceptions\TierOverlapException;
use Illuminate\Support\Collection;

class PackagePriceCalculator
{
    /**
     * Calculate dynamic pricing, taxes (IGV), and item breakdown for a package.
     *
     * @param  array  $extras  List of additional items [['product_id' => ..., 'quantity' => ..., 'unit_price' => ...], ...]
     * @return array{
     *     package_id: int|null,
     *     package_name: string,
     *     guests: int,
     *     price_mode: string,
     *     package_price: float,
     *     subtotal: float,
     *     igv: float,
     *     total: float,
     *     breakdown: array<int, array>
     * }
     *
     * @throws GuestOutOfRangeException
     * @throws TierOverlapException
     */
    public function calculate(Package|int $package, int $guests, array $extras = []): array
    {
        $packageModel = $this->resolvePackage($package);

        // 1. Validate guest bounds & tiers
        $this->validateGuestRanges($packageModel, $guests);

        // 2. Calculate base package price
        $packagePrice = $this->calculatePackageBasePrice($packageModel, $guests);

        // 3. Process package included items
        $itemsBreakdown = $this->distributePackageItems($packageModel, $packagePrice, $guests);

        // 4. Process extras
        $extrasBreakdown = $this->processExtras($extras, $guests);

        // 5. Consolidate breakdown
        $allBreakdown = array_merge($itemsBreakdown, $extrasBreakdown);

        // 6. Aggregate subtotal, IGV, and total with strict 2-decimal rounding
        $subtotal = 0.00;
        $igv = 0.00;
        $total = 0.00;

        foreach ($allBreakdown as $line) {
            $subtotal += (float) $line['subtotal'];
            $igv += (float) $line['igv'];
            $total += (float) $line['total'];
        }

        $subtotal = round($subtotal, 2);
        $igv = round($igv, 2);
        $total = round($total, 2);

        return [
            'package_id' => $packageModel->id,
            'package_name' => (string) $packageModel->name,
            'guests' => $guests,
            'price_mode' => (string) $packageModel->price_mode,
            'package_price' => round($packagePrice, 2),
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'breakdown' => $allBreakdown,
        ];
    }

    /**
     * Validate that tiers have no overlapping ranges and that guests count falls inside limits.
     *
     * @throws TierOverlapException
     * @throws GuestOutOfRangeException
     */
    public function validateGuestRanges(Package $package, int $guests): void
    {
        $min = (int) $package->min_guests;
        $max = $package->max_guests !== null ? (int) $package->max_guests : null;

        // Check package global bounds
        if ($guests < $min || ($max !== null && $guests > $max)) {
            throw new GuestOutOfRangeException($guests, $min, $max);
        }

        // If tiered, validate tier overlaps and tier coverage
        if ($package->price_mode === 'tiered') {
            $tiers = $this->getTiersCollection($package)->sortBy('from_guests')->values();

            if ($tiers->isEmpty()) {
                throw new GuestOutOfRangeException($guests, $min, $max, 'El paquete no tiene tramos de precios configurados.');
            }

            // Check tier overlaps
            $tierCount = $tiers->count();
            for ($i = 0; $i < $tierCount; $i++) {
                $current = $tiers[$i];
                $cFrom = (int) $current->from_guests;
                $cTo = (int) $current->to_guests;

                for ($j = $i + 1; $j < $tierCount; $j++) {
                    $next = $tiers[$j];
                    $nFrom = (int) $next->from_guests;
                    $nTo = (int) $next->to_guests;

                    // Overlap condition: max(cFrom, nFrom) <= min(cTo, nTo)
                    if (max($cFrom, $nFrom) <= min($cTo, $nTo)) {
                        throw new TierOverlapException($cFrom, $cTo, $nFrom, $nTo);
                    }
                }
            }

            // Check if guests falls into one of the tiers
            $matchingTier = $tiers->first(function ($tier) use ($guests) {
                return $guests >= (int) $tier->from_guests && $guests <= (int) $tier->to_guests;
            });

            if (! $matchingTier) {
                throw new GuestOutOfRangeException(
                    $guests,
                    (int) $tiers->min('from_guests'),
                    (int) $tiers->max('to_guests'),
                    "La cantidad de invitados ({$guests}) no coincide con ningún tramo de precios configurado."
                );
            }
        }
    }

    /**
     * Calculate package price depending on price_mode (fixed, per_guest, tiered).
     */
    protected function calculatePackageBasePrice(Package $package, int $guests): float
    {
        switch ($package->price_mode) {
            case 'fixed':
                return round((float) $package->base_price, 2);

            case 'per_guest':
                return round((float) $package->base_price * $guests, 2);

            case 'tiered':
                $tiers = $this->getTiersCollection($package);
                $matchingTier = $tiers->first(function ($tier) use ($guests) {
                    return $guests >= (int) $tier->from_guests && $guests <= (int) $tier->to_guests;
                });

                if (! $matchingTier) {
                    throw new GuestOutOfRangeException($guests, (int) $package->min_guests, $package->max_guests ? (int) $package->max_guests : null);
                }

                if ($matchingTier instanceof PackagePriceTier) {
                    return $matchingTier->calculatePrice($guests);
                }

                $priceType = (string) ($matchingTier->price_type ?? 'total');
                if ($priceType === 'per_guest') {
                    return round((float) $matchingTier->price * $guests, 2);
                }

                return round((float) $matchingTier->price, 2);

            default:
                return round((float) $package->base_price, 2);
        }
    }

    /**
     * Distribute and calculate individual tax affection for included package items.
     */
    protected function distributePackageItems(Package $package, float $packagePrice, int $guests): array
    {
        $items = $this->getItemsCollection($package);
        if ($items->isEmpty()) {
            // Standalone package service line
            return [$this->createLineBreakdown(
                name: $package->name,
                type: 'package_included',
                quantity: 1,
                total: $packagePrice,
                igvPercent: 18,
                taxAffection: '10',
                isService: true,
                isMandatory: true,
                notes: 'Servicio integral de paquete'
            )];
        }

        // Calculate item weights based on unit prices or default equal weights
        $weightedItems = [];
        $totalWeight = 0.00;

        foreach ($items as $item) {
            $qty = $item instanceof PackageItem ? $item->calculateQuantity($guests) : (float) ($item->quantity ?? 1);
            $unitPrice = $this->resolveItemReferenceUnitPrice($item);
            $weight = $unitPrice > 0 ? round($unitPrice * $qty, 4) : 0.00;

            $weightedItems[] = [
                'model' => $item,
                'qty' => $qty,
                'weight' => $weight,
            ];
            $totalWeight += $weight;
        }

        $lines = [];
        $allocatedTotal = 0.00;
        $count = count($weightedItems);

        foreach ($weightedItems as $idx => $entry) {
            $item = $entry['model'];
            $qty = $entry['qty'];
            $weight = $entry['weight'];

            // Compute allocated gross total
            if ($idx === $count - 1) {
                // Last item gets remainder to ensure exact sum equals packagePrice
                $itemTotal = round($packagePrice - $allocatedTotal, 2);
            } else {
                if ($totalWeight > 0) {
                    $itemTotal = round($packagePrice * ($weight / $totalWeight), 2);
                } else {
                    $itemTotal = round($packagePrice / $count, 2);
                }
                $allocatedTotal += $itemTotal;
            }

            $taxInfo = $this->resolveTaxAffection($item);
            $isService = $this->resolveIsService($item);

            $name = $item instanceof PackageItem
                ? $item->name
                : (string) ($item->name ?? $item->notes ?? 'Ítem de paquete');

            $lines[] = $this->createLineBreakdown(
                name: $name,
                type: 'package_included',
                quantity: $qty,
                total: $itemTotal,
                igvPercent: $taxInfo['igv_percent'],
                taxAffection: $taxInfo['codigo_afectacion'],
                isService: $isService,
                isMandatory: (bool) ($item->is_mandatory ?? true),
                notes: (string) ($item->notes ?? null)
            );
        }

        return $lines;
    }

    /**
     * Process optional extras and calculate tax per item.
     */
    protected function processExtras(array $extras, int $guests): array
    {
        $lines = [];

        foreach ($extras as $extra) {
            $qty = isset($extra['quantity']) ? (float) $extra['quantity'] : 1.0;
            if (isset($extra['is_per_guest']) && $extra['is_per_guest']) {
                $qty = round($qty * $guests, 2);
            }

            if ($qty <= 0) {
                continue;
            }

            $product = null;
            $cocktail = null;
            $name = $extra['name'] ?? 'Extra';
            $unitPrice = isset($extra['unit_price']) ? (float) $extra['unit_price'] : 0.00;

            if (! empty($extra['product_id'])) {
                $product = Product::with('igvTypeAffection')->find($extra['product_id']);
                if ($product) {
                    $name = $product->descripcion;
                    if ($unitPrice <= 0) {
                        $unitPrice = (float) $product->precio_venta;
                    }
                }
            } elseif (! empty($extra['cocktail_id'])) {
                $cocktail = CocktailMenuItem::with('product.igvTypeAffection')->find($extra['cocktail_id']);
                if ($cocktail) {
                    $name = $cocktail->nombre;
                    if ($unitPrice <= 0) {
                        $unitPrice = (float) ($cocktail->precio ?: ($cocktail->product ? $cocktail->product->precio_venta : 0.00));
                    }
                }
            }

            $lineTotal = round($unitPrice * $qty, 2);
            $taxInfo = $this->resolveTaxAffectionForExtra($product, $cocktail);
            $isService = $this->resolveIsServiceForExtra($product, $cocktail);

            $lines[] = $this->createLineBreakdown(
                name: $name,
                type: 'extra',
                quantity: $qty,
                total: $lineTotal,
                igvPercent: $taxInfo['igv_percent'],
                taxAffection: $taxInfo['codigo_afectacion'],
                isService: $isService,
                isMandatory: false,
                notes: $extra['notes'] ?? 'Ítem extra opcional'
            );
        }

        return $lines;
    }

    /**
     * Helper to create a single line item breakdown.
     */
    public function createLineBreakdown(
        string $name,
        string $type,
        float $quantity,
        float $total,
        int $igvPercent,
        string $taxAffection,
        bool $isService,
        bool $isMandatory,
        ?string $notes = null
    ): array {
        $total = round($total, 2);

        if ($igvPercent === 18) {
            // Gravado: total = subtotal * 1.18
            $subtotal = round($total / 1.18, 2);
            $igv = round($total - $subtotal, 2);
        } else {
            // Exonerado (20) o Inafecto (30)
            $subtotal = $total;
            $igv = 0.00;
        }

        $unitPrice = $quantity > 0 ? round($total / $quantity, 2) : $total;

        return [
            'name' => $name,
            'type' => $type, // package_included, extra
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'igv_percent' => $igvPercent,
            'tax_affection' => $taxAffection, // '10' (Gravado), '20' (Exonerado), '30' (Inafecto)
            'total' => $total,
            'is_service' => $isService,
            'is_mandatory' => $isMandatory,
            'notes' => $notes,
        ];
    }

    /**
     * Determine tax affection code ('10', '20', '30') and IGV percent (18 or 0).
     *
     * @return array{codigo_afectacion: string, igv_percent: int}
     */
    public function resolveTaxAffection(mixed $item): array
    {
        $product = null;
        if ($item instanceof PackageItem) {
            $product = $item->relationLoaded('product') ? $item->product : ($item->product_id ? $item->product : null);
            if (! $product && $item->relationLoaded('cocktail') && $item->cocktail) {
                $product = $item->cocktail->relationLoaded('product') ? $item->cocktail->product : null;
            }
        } elseif (is_object($item) && isset($item->product)) {
            $product = $item->product;
        }

        if ($product) {
            $affection = $product->relationLoaded('igvTypeAffection') ? $product->igvTypeAffection : ($product->idcodigo_igv ? $product->igvTypeAffection : null);
            $code = $affection ? (string) $affection->codigo : null;

            if ($code === '20') {
                return ['codigo_afectacion' => '20', 'igv_percent' => 0];
            }
            if ($code === '30') {
                return ['codigo_afectacion' => '30', 'igv_percent' => 0];
            }
            if ($code === '10' || (int) $product->igv > 0) {
                return ['codigo_afectacion' => '10', 'igv_percent' => 18];
            }
        }

        // Default to Gravado 18% for bar services/events
        return ['codigo_afectacion' => '10', 'igv_percent' => 18];
    }

    protected function resolveTaxAffectionForExtra(?Product $product, ?CocktailMenuItem $cocktail): array
    {
        $targetProduct = $product ?? optional($cocktail)->product;

        if ($targetProduct) {
            $affection = $targetProduct->relationLoaded('igvTypeAffection') ? $targetProduct->igvTypeAffection : ($targetProduct->idcodigo_igv ? $targetProduct->igvTypeAffection : null);
            $code = $affection ? (string) $affection->codigo : null;

            if ($code === '20') {
                return ['codigo_afectacion' => '20', 'igv_percent' => 0];
            }
            if ($code === '30') {
                return ['codigo_afectacion' => '30', 'igv_percent' => 0];
            }
            if ($code === '10' || (int) $targetProduct->igv > 0) {
                return ['codigo_afectacion' => '10', 'igv_percent' => 18];
            }
        }

        return ['codigo_afectacion' => '10', 'igv_percent' => 18];
    }

    public function resolveIsService(mixed $item): bool
    {
        if ($item instanceof PackageItem) {
            if ($item->cocktail_id) {
                return true;
            }
            if ($item->relationLoaded('product') && $item->product) {
                return (int) $item->product->opcion === 2;
            }
            if ($item->product_id && $item->product) {
                return (int) $item->product->opcion === 2;
            }
        }

        if (is_object($item)) {
            if (isset($item->cocktail_id) && $item->cocktail_id) {
                return true;
            }
            if (isset($item->product) && isset($item->product->opcion)) {
                return (int) $item->product->opcion === 2;
            }
        }

        return true;
    }

    protected function resolveIsServiceForExtra(?Product $product, ?CocktailMenuItem $cocktail): bool
    {
        if ($cocktail) {
            return true;
        }

        if ($product) {
            return (int) $product->opcion === 2;
        }

        return true;
    }

    protected function resolveItemReferenceUnitPrice(mixed $item): float
    {
        if ($item instanceof PackageItem) {
            if ($item->unit_price !== null && (float) $item->unit_price > 0) {
                return (float) $item->unit_price;
            }
            if ($item->relationLoaded('cocktail') && $item->cocktail && (float) $item->cocktail->precio > 0) {
                return (float) $item->cocktail->precio;
            }
            if ($item->relationLoaded('product') && $item->product && (float) $item->product->precio_venta > 0) {
                return (float) $item->product->precio_venta;
            }
        }

        if (is_object($item) && isset($item->unit_price) && (float) $item->unit_price > 0) {
            return (float) $item->unit_price;
        }

        return 1.00; // Equal weight fallback
    }

    protected function resolvePackage(Package|int $package): Package
    {
        if ($package instanceof Package) {
            return $package;
        }

        return Package::with([
            'items.product.igvTypeAffection',
            'items.cocktail.product.igvTypeAffection',
            'priceTiers',
        ])->findOrFail($package);
    }

    protected function getTiersCollection(Package $package): Collection
    {
        return $package->relationLoaded('priceTiers')
            ? $package->priceTiers
            : $package->priceTiers()->get();
    }

    protected function getItemsCollection(Package $package): Collection
    {
        return $package->relationLoaded('items')
            ? $package->items
            : $package->items()->get();
    }
}
