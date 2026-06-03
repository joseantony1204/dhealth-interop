<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blameable;

class Ihcetransmisiones extends Model
{
    use SoftDeletes, Blameable; 
    protected $table = 'ihce_transmisiones';
    protected $fillable = ['cita_id', 'configuracion_id', 'estado', 'typerda', 'source_snapshot_data', 'vida_code', 'last_payload_sent', 'response_log', 'retry_count', 'last_attempt_at', 'created_by', 'updated_by', 'deleted_by'];

    protected $casts = [
        'response_log' => 'array',
        'last_attempt_at' => 'datetime'
    ];

    public function configuracion(): BelongsTo {
        return $this->belongsTo(Ihceconfiguraciones::class, 'configuracion_id');
    }

    /**
     * Registra un log histórico de respuestas de forma acumulativa sin sobreescribir fallos previos
     */
    public function recordResponseLog(int $httpCode, array $responseBody, bool $isError = false)
    {
        $currentLogs = $this->response_log ?? [];
        $currentLogs[] = [
            'timestamp' => now()->toIso8601String(),
            'http_code' => $httpCode,
            'is_error' => $isError,
            'body' => $responseBody
        ];

        $this->response_log = $currentLogs;
        $this->save();
    }

    /**
     * Accesor para source_snapshot_data
     */
    protected function getSourceSnapshotDataAttribute($value)
    {
        // Si por alguna razón está vacío, retornamos un array vacío, de lo contrario lo decodificamos
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Accesor para last_payload_sent
     */
    protected function getLastPayloadSentAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}