<?php

namespace App\Http\Controllers;

use App\Models\Ihceambientes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\{DB,Auth};

class IhceambientesController extends Controller
{
    /**
     * Lista todos los ambientes registrados en la plataforma
     */
    public function index()
    {
        $ambientes = Ihceambientes::all();

        return Inertia::render('ihce/ambientes/index', [
            'ambientes' => $ambientes
        ]);
    }

    /**
     * Almacena un nuevo ambiente (ej. "Staging", "Sandbox")
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:20|unique:ihce_ambientes,codigo',
            'nombre' => 'required|string|max:50',
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'required|boolean'
        ]);

        Ihceambientes::create($validated);

        return redirect()->back()->with('success', 'Ambiente de interoperabilidad creado con éxito.');
    }

    /**
     * Actualiza los datos o el estado de activación de un entorno
     */
    public function update(Request $request, $id)
    {
        $ambiente = Ihceambientes::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'required|boolean'
        ]);

        $ambiente->update($validated);

        return redirect()->back()->with('success', 'Parámetros del ambiente actualizados.');
    }

    public function destroy($id)
    {
        // Validación de rol administrativo
        if (Auth::user()->role !== 'admin') {
            return redirect()->back()->with('error', 'No tiene los permisos necesarios.');
        }

        $ambiente = Ihceambientes::findOrFail($id);

        // Control de integridad: Evita romper la base de datos si el ambiente tiene credenciales inyectadas
        if ($ambiente->configuraciones()->exists()) {
            return redirect()->back()->with('error', 'No se puede aprovisionar la eliminación. Existen credenciales KMS activas vinculadas a este nodo.');
        }

        $ambiente->delete();

        return redirect()->back()->with('message', 'Nodo de infraestructura removido del clúster.');
    }
}