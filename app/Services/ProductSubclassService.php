<?php

namespace App\Services;

use App\Models\ProductSubclass;

/**
 * Writes the job order subclasses.
 *
 * Archiving a subclass never detaches the products or the job orders that
 * already point at it, because the historical classification has to stay
 * readable.
 */
class ProductSubclassService
{
    /**
     * Create a subclass inside its class.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductSubclass
    {
        return ProductSubclass::create([
            'name' => trim((string) $data['name']),
            'product_class_id' => (int) $data['product_class_id'],
        ]);
    }

    /**
     * Change the name of a subclass or the class it belongs to.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProductSubclass $productSubclass, array $data): ProductSubclass
    {
        $productSubclass->fill([
            'name' => trim((string) $data['name']),
            'product_class_id' => (int) $data['product_class_id'],
        ]);

        $productSubclass->save();

        return $productSubclass;
    }

    /**
     * Remove a subclass from the active list.
     *
     * The job order line items that use the subclass keep their reference, so
     * no historical job order is changed.
     */
    public function archive(ProductSubclass $productSubclass): void
    {
        $productSubclass->delete();
    }
}
