<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SedeActivaController extends Controller
{
    public function cambiar(Request $request)
    {
        $request->validate(['sede_id' => 'required|integer']);

        $user       = auth()->user();
        $permitidas = array_map('intval', $user->sedesPermitidasIds());
        $sedeId     = (int) $request->sede_id;

        if (!$user->esPropietario() || !in_array($sedeId, $permitidas, true)) {
            abort(403, 'No autorizado');
        }

        session(['sede_id' => $sedeId]);

        return back();
    }
}