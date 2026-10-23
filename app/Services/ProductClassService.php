<?php

namespace App\Services;

use App\Models\ProductClass;

/**
 * Writes the job order classes.
 *
 * Archiving a class never detaches the subclasses, the products or the job
 * orders that already point at it, because the historical classification has
 * to stay readable.
 */
class ProductClassService
{
    /**
     * Create a class.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductClass
    {
        return ProductClass::create([
            'name' => trim((string) $data['name']),
        ]);
    }

    /**
     * Change the name of a class.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProductClass $productClass, array $data): ProductClass
    {
        $productClass->fill([
            'name' => trim((string) $data['name']),
        ]);

        $productClass->save();

        return $productClass;
    }

    /**
     * Remove a class from the active list.
     *
     * The subclasses and the job order line items that use the class keep
     * their reference, so no historical job order is changed.
     */
    public function archive(ProductClass $productClass): void
    {
        $productClass->delete();
    }
}
