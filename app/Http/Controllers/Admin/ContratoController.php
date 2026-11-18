<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Contratos firmados de cada negocio (Panel Admin, solo superadmin).
 */
class ContratoController extends Controller
{
    public function index(Tenant $tenant)
    {
        return response()->json([
            'success'   => true,
            'contratos' => $tenant->contratos()->with('usuario:id,name')->get()->map(fn ($c) => [
                'id'        => $c->id,
                'nombre'    => $c->nombre,
                'tamano'    => $c->tamano,
                'firma'     => $c->fecha_firma->format('d/m/Y'),
                'vigencia'  => $c->vigencia_desde && $c->vigencia_hasta
                    ? $c->vigencia_desde->format('d/m/Y') . ' – ' . $c->vigencia_hasta->format('d/m/Y')
                    : null,
                'notas'     => $c->notas,
                'subido'    => $c->created_at->format('d/m/Y h:i a') . ($c->usuario ? ' · ' . $c->usuario->name : ''),
                'descargar' => "/admin/contratos/{$c->id}/descargar",
            ]),
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'archivo'        => 'required|file|mimes:pdf|max:15360',
            'fecha_firma'    => 'required|date|before_or_equal:today',
            'vigencia_desde' => 'nullable|date',
            'vigencia_hasta' => 'nullable|date|after_or_equal:vigencia_desde',
            'notas'          => 'nullable|string|max:255',
        ], [
            'archivo.required' => 'Elige el PDF del contrato firmado.',
            'archivo.mimes'    => 'El contrato debe ser un archivo PDF.',
            'archivo.max'      => 'El PDF no puede pesar más de 15 MB.',
            'fecha_firma.required' => 'Escribe la fecha en que se firmó.',
        ]);

        $archivo = $request->file('archivo');
        $ruta    = $archivo->storeAs("contratos/{$tenant->id}", now()->format('Ymd-His') . '-' . Str::random(8) . '.pdf', 'local');

        $contrato = $tenant->contratos()->create([
            'nombre'         => Str::limit($archivo->getClientOriginalName(), 180, ''),
            'archivo'        => $ruta,
            'tamano'         => (int) $archivo->getSize(),
            'fecha_firma'    => $data['fecha_firma'],
            'vigencia_desde' => $data['vigencia_desde'] ?? null,
            'vigencia_hasta' => $data['vigencia_hasta'] ?? null,
            'notas'          => $data['notas'] ?? null,
            'subido_por'     => auth()->id(),
        ]);

        return response()->json(['success' => true, 'mensaje' => "Contrato de {$tenant->nombre} cargado", 'id' => $contrato->id]);
    }

    public function descargar(Contrato $contrato)
    {
        abort_unless(Storage::disk('local')->exists($contrato->archivo), 404, 'El archivo del contrato no está en el servidor.');

        return Storage::disk('local')->download($contrato->archivo, $contrato->nombre, ['Content-Type' => 'application/pdf']);
    }

    public function destroy(Contrato $contrato)
    {
        Storage::disk('local')->delete($contrato->archivo);
        $contrato->delete();

        return response()->json(['success' => true, 'mensaje' => 'Contrato eliminado']);
    }
}
