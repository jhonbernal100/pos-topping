<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (session()->has('tenant_id')) {
            // qualifyColumn evita "columna ambigua" cuando la consulta une varias tablas con tenant_id
            $builder->where($model->qualifyColumn('tenant_id'), session('tenant_id'));
        }
    }
}