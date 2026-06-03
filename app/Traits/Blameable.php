<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Blameable
{
    public static function bootBlameable()
    {
        // Al crear un registro
        static::creating(function ($model) {
            if (Auth::check()) {
                if (in_array('created_by', $model->getFillable())) {
                    $model->created_by = Auth::id();
                }
            }
        });

        // Al actualizar un registro
        static::updating(function ($model) {
            if (Auth::check()) {
                if (in_array('updated_by', $model->getFillable())) {
                    $model->updated_by = Auth::id();
                }
            }
        });

        // Al eliminar (SoftDelete) un registro
        static::deleting(function ($model) {
            if (Auth::check()) {
                if (in_array('deleted_by', $model->getFillable())) {
                    // Guardamos antes de que se ejecute el softdelete destructivo
                    $model->timestamps = false; // Evita alterar updated_at al borrar
                    $model->deleted_by = Auth::id();
                    $model->save();
                }
            }
        });
    }
}