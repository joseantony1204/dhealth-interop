<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blameable;

class Ihceconfiguraciones extends Model
{
    use SoftDeletes, Blameable; 
    protected $table = 'ihce_configuraciones';
    protected $fillable = ['sede_id', 'ambiente_id', 'endpoint_url', 'tenant_id', 'scope', 'apim_subs_key', 'client_id', 'client_secret', 'created_by', 'updated_by', 'deleted_by'];

    public function ambiente(): BelongsTo {
        return $this->belongsTo(Ihceambientes::class, 'ambiente_id');
    }

    // Mutadores para encriptar automáticamente el ClientSecret en la Base de Datos usando AES-256
    public function setClientSecretAttribute($value) {
        $this->attributes['client_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getClientSecretAttribute($value) {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}