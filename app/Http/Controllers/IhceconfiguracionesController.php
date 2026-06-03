<?php

namespace App\Http\Controllers;

use App\Models\Ihceconfiguraciones;
use App\Models\Ihceambientes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\{DB,Auth};

class IhceconfiguracionesController extends Controller
{
    /**
     * Muestra la interfaz del CRUD de configuraciones
     */
    public function index()
    {
        return Inertia::render('ihce/configuraciones/index', [
            'configuraciones' => Ihceconfiguraciones::with(['ambiente'])->get(),
            'ambientes' => Ihceambientes::where('estado', 1)->get(), // Adaptado a tu campo 'estado'
            // Planchamos las sedes de D-Health para mapearlas en el select del frontend
            'sedes' => [
                ['id' => 1, 'nombre' => 'Sede Principal'],
                ['id' => 2, 'nombre' => 'Sede Secundaria'],
                ['id' => 3, 'nombre' => 'Sede de Pruebas']
            ] 
            //'sedes' => DB::table('cfsedes')->select('id', 'nombre')->get() 
        ]);
    }

    /**
     * Almacena una nueva configuración de credenciales cifradas para una sede
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sede_id' => 'required|integer',
            'ambiente_id' => 'required|exists:ihce_ambientes,id',
            'endpoint_url' => 'required|url',
            'tenant_id' => 'required|string|max:100',
            'scope' => 'required|string|max:100',
            'apim_subs_key' => 'required|string|max:150',
            'client_id' => 'required|string|max:150',
            'client_secret' => 'required|string',
        ]);

        Ihceconfiguraciones::create($validated);

        return redirect()->back()->with('success', 'Configuración de interoperabilidad guardada con éxito.');
    }

    /**
     * Actualiza los parámetros de conexión de una sede específica
     */
    public function update(Request $request, $id)
    {
        $configuracion = Ihceconfiguraciones::findOrFail($id);

        $validated = $request->validate([
            'endpoint_url' => 'required|url',
            'tenant_id' => 'required|string|max:100',
            'scope' => 'required|string|max:100',
            'apim_subs_key' => 'required|string|max:150',
            'client_id' => 'required|string|max:150',
            'client_secret' => 'nullable|string',
        ]);

        // Si el secreto viene vacío, preservamos el que ya está cifrado en la BD
        if (empty($validated['client_secret'])) {
            unset($validated['client_secret']);
        }

        $configuracion->update($validated);

        return redirect()->back()->with('success', 'Configuración de entorno actualizada correctamente.');
    }

    public function destroy($id)
    {
        // 1. Filtro de seguridad estricto (Opcional, lo mantengo comentado como lo tenías)
        /*if (Auth::user()->role !== 'admin') {
            return redirect()->back()->with('error', 'Acción no autorizada.');
        }*/

        // 2. Localizar el recurso
        $configuracion = Ihceconfiguraciones::findOrFail($id);

        // 3. Registrar el usuario que elimina antes de disparar el SoftDelete
        $configuracion->deleted_by = Auth::id(); // Guarda el ID del usuario autenticado
        $configuracion->save(); // Persiste el cambio de 'deleted_by'

        // 4. Ejecutar el borrado lógico (Actualiza automáticamente 'deleted_at')
        $configuracion->delete();

        // 5. Retornar respuesta compatible con Inertia
        return redirect()->back()->with('message', 'Parámetros de interoperabilidad revocados con éxito.');
    }
}