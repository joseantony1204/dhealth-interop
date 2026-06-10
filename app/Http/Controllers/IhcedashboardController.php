<?php

namespace App\Http\Controllers;

use App\Models\Ihcetransmisiones;
use Inertia\Inertia;
use Carbon\Carbon;

class IhcedashboardController extends Controller
{
    public function index()
    {
        // 1. Forzamos a trabajar bajo la zona horaria UTC para emparejar con el JSON de la BD
        $horaActual = Carbon::now('UTC');
        $hace24Horas = $horaActual->copy()->subHours(24);

        // 2. TRANSMISIONES PAGINADAS
        $transmisiones = Ihcetransmisiones::select([
                'id', 'retry_count', 'last_attempt_at', 'estado', 'typerda', 'configuracion_id', 'cita_id', 'created_at'
            ])
            ->with(['configuracion.ambiente'])
            ->orderBy('created_at', 'DESC')
            ->paginate(3)
            ->withQueryString();

        // 3. CONSULTA ÚNICA EN MEMORIA
        $universologs24h = Ihcetransmisiones::whereBetween('created_at', [$hace24Horas, $horaActual])
            ->select('estado', 'retry_count', 'created_at')
            ->get();

        // 4. MAPEO PARA LOS NODOS (Aquí convertimos visualmente a la hora local para la tabla si lo deseas)
        $nodosActivos = collect($transmisiones->items())->map(function ($transmision) {
            $url = $transmision->configuracion->endpoint_url ?? '';
            $seguridad = str_contains($url, 'https') ? 'TLS 1.3' : 'HTTP/Inseguro';

            // Ajustamos la fecha de UTC a tu hora local (América/Bogota - UTC-5) al mostrar en la interfaz
            $fechaLocal = $transmision->created_at 
                ? Carbon::parse($transmision->created_at)->timezone('America/Bogota')->format('d/m H:i') 
                : 'N/A';

            return [
                'id' => $transmision->id,
                'cita_id' => $transmision->cita_id,
                'identificador' => $transmision->typerda,
                'mecanismo' => 'REST / JSON',
                'seguridad' => $seguridad,
                'token' => $transmision->configuracion->tenant_id ?? 'Sin Tenant',
                'estado' => $transmision->estado,
                'reintentos' => $transmision->retry_count,
                'fecha' => $fechaLocal
            ];
        });

        // 5. TELEMETRÍA DE LA GRÁFICA EN TIEMPO REAL (Sincronizada en UTC)
        $chartDataRaw = collect(range(14, 0, -2))->map(function ($hoursAgo) use ($universologs24h, $horaActual) {
            $timeMarker = $horaActual->copy()->subHours($hoursAgo);
            $limiteInferior = $timeMarker->copy()->subHours(2);

            // Filtro de control temporal estricto
            if ($limiteInferior->greaterThanOrEqualTo($horaActual) || $timeMarker->greaterThan($horaActual)) {
                return [
                    'hora' => $timeMarker->timezone('America/Bogota')->format('H:i'), // Mostramos etiqueta local
                    'peticiones' => 0,
                    'es_futuro' => true
                ];
            }

            // Conteo exacto emparejando los objetos Carbon en la misma zona (UTC)
            $conteoReal = $universologs24h->filter(function($log) use ($limiteInferior, $timeMarker) {
                $logDate = Carbon::parse($log->created_at);
                return $logDate->greaterThanOrEqualTo($limiteInferior) && $logDate->lessThanOrEqualTo($timeMarker);
            })->count();

            return [
                'time_obj' => $timeMarker,
                'peticiones' => $conteoReal,
                'es_futuro' => false
            ];
        });

        $maxPeticionesPeriodo = $chartDataRaw->where('es_futuro', false)->max('peticiones');

        // Asignación final de alturas dinámicas
        $chartDataFinal = $chartDataRaw->map(function ($item) use ($maxPeticionesPeriodo) {
            if ($item['es_futuro'] || $item['peticiones'] === 0) {
                $claseAltura = "h-0";
            } else if ($maxPeticionesPeriodo === 0) {
                $claseAltura = "h-12";
            } else {
                $porcentaje = ($item['peticiones'] / $maxPeticionesPeriodo) * 100;

                if ($porcentaje <= 25)  $claseAltura = "h-12";
                if ($porcentaje > 25 && $porcentaje <= 50)  $claseAltura = "h-24";
                if ($porcentaje > 50 && $porcentaje <= 75)  $claseAltura = "h-36";
                if ($porcentaje > 75) $claseAltura = "h-48";
            }

            // Al pasar la hora al frontend, la transformamos a tu uso horario local (Bogota / UTC-5)
            $horaMostrar = isset($item['es_futuro']) && $item['es_futuro'] 
                ? $item['hora'] 
                : $item['time_obj']->copy()->timezone('America/Bogota')->format('H:i');

            return [
                'hora' => $horaMostrar,
                'carga' => $claseAltura,
                'peticiones' => number_format($item['peticiones']),
                'es_futuro' => $item['es_futuro']
            ];
        });

        // 6. HISTORIAL DE DISPONIBILIDAD (Mapa de Calor de 24h normalizado)
        $uptimeBlocks = collect(range(23, 0, -1))->map(function ($hourAgo) use ($universologs24h, $horaActual) {
            $time = $horaActual->copy()->subHours($hourAgo);
            
            $metricasHora = $universologs24h->filter(function ($item) use ($time) {
                if (!$item->created_at) return false;
                $logDate = Carbon::parse($item->created_at);
                return $logDate->format('Y-m-d H') === $time->format('Y-m-d H');
            });

            $status = 'bg-emerald-500'; 
            $detalle = 'Operacional (Sin Novedad)';

            if ($metricasHora->isNotEmpty()) {
                $total = $metricasHora->count();
                $rechazadas = $metricasHora->where('estado', 'REJECTED')->count();
                $pendientes = $metricasHora->where('estado', 'PENDING')->count();
                $reintentos = $metricasHora->where('retry_count', '>', 0)->count();

                if ($rechazadas > 0 && ($rechazadas / $total) >= 0.25) {
                    $status = 'bg-rose-500';
                    $detalle = "Interrupción: {$rechazadas} payloads REJECTED";
                } elseif ($pendientes > 0 || $reintentos > 0 || $rechazadas > 0) {
                    $status = 'bg-amber-500';
                    $detalle = "Latencia: {$pendientes} PND / {$rechazadas} REJ / {$reintentos} Rtry";
                } else {
                    $detalle = "Saludable: {$total} reqs procesadas";
                }
            }

            return [
                'hora' => $time->copy()->timezone('America/Bogota')->format('H:00'),
                'status' => $status,
                'detalle' => $detalle
            ];
        });

        // 7. MÉTRICAS DE HARDWARE / ESTADOS
        $totalLogs24h = $universologs24h->count();
        if ($totalLogs24h > 0) {
            $aprobadas = $universologs24h->where('estado', 'APPROVED')->count();
            $pendientes = $universologs24h->where('estado', 'PENDING')->count();
            $rechazadas = $universologs24h->where('estado', 'REJECTED')->count();

            $rendimientoAPI = [
                'efectividad' => round(($aprobadas / $totalLogs24h) * 100),
                'encola'      => round(($pendientes / $totalLogs24h) * 100),
                'rechazo'     => round(($rechazadas / $totalLogs24h) * 100),
            ];
        } else {
            $rendimientoAPI = ['efectividad' => 100, 'encola' => 0, 'rechazo' => 0];
        }

        $totalBloques = $uptimeBlocks->count();
        $bloquesVerdes = $uptimeBlocks->where('status', 'bg-emerald-500')->count();
        $uptimeGlobalCalcular = number_format(($bloquesVerdes / $totalBloques) * 100, 2) . '%';

        return Inertia::render('Dashboard', [
            'transmisiones' => $transmisiones,
            'telemetria' => [
                'chartData' => $chartDataFinal,
                'uptimeBlocks' => $uptimeBlocks,
                'hardware' => $rendimientoAPI,
                'nodos' => $nodosActivos,
                'uptimeGlobal' => $uptimeGlobalCalcular 
            ]
        ]);
    }
}