<?php

namespace App\Traits;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCustomer
{
    public static function bootBelongsToCustomer(): void
    {
        static::creating(function ($model) {
            if (empty($model->customer_id) && auth()->check() && auth()->user()->customer) {
                $model->customer_id = auth()->user()->customer->id;
            }
        });

        // If authenticated user is a customer (not admin), scope automatically
        if (auth()->check() && !auth()->user()->isAdmin()) {
            static::addGlobalScope('customer', function (Builder $builder) {
                if (auth()->user()->customer) {
                    $builder->where($builder->getModel()->getTable() . '.customer_id', auth()->user()->customer->id);
                } else {
                    $builder->whereRaw('1 = 0'); // Deny all if no customer record attached
                }
            });
        }
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
