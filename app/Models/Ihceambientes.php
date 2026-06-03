<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blameable;

class Ihceambientes extends Model
{
    use SoftDeletes, Blameable; 
    protected $table = 'ihce_ambientes';
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'estado', 'created_by', 'updated_by', 'deleted_by'];
    
    public function configuraciones(): HasMany {
        return $this->hasMany(Ihceconfiguraciones::class);
    }
}