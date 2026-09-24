<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use App\Models\TrialRequest;

class TrialController extends Controller
{
    public function index()
    {
        return view('trial.paso1');
    }

    public function procesarRut(Request $request)
    {
        $request->validate([
            'imagen' => 'required|string',
        ]);

        try {
            $keyFile = json_decode(
                file_get_contents(base_path('gcp-vision-key.json')),
                true
            );

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/cloud-vision',
                $keyFile
            );

            $token = $credentials->fetchAuthToken(HttpHandlerFactory::build());

            $visionResponse = Http::withHeaders([
                'Authorization' => "Bearer {$token['access_token']}",
                'Content-Type'  => 'application/json',
            ])->post('https://vision.googleapis.com/v1/images:annotate', [
                'requests' => [[
                    'image'    => ['content' => $request->imagen],
                    'features' => [['type' => 'TEXT_DETECTION', 'maxResults' => 1]],
                ]],
            ]);

            $textoOCR = $visionResponse->json(
                'responses.0.textAnnotations.0.description'
            ) ?? '';

            if (empty($textoOCR)) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No se pudo leer el RUT. Intenta con mejor iluminacion.',
                ], 422);
            }

            $prompt = "Eres un experto en documentos tributarios colombianos.
Analiza este texto extraído de un RUT colombiano:

\"$textoOCR\"

Devuélveme ÚNICAMENTE un JSON válido con esta estructura, sin texto adicional:
{
  \"nombre_negocio\": \"razón social o nombre del negocio\",
  \"nit\": \"número de NIT sin dígito de verificación\",
  \"nombre_representante\": \"nombre del representante legal o propietario\",
  \"ciudad\": \"ciudad o municipio\",
  \"direccion\": \"dirección del establecimiento o null\"
}";

            $geminiResponse = Gemini::generativeModel(
                model: 'models/gemini-2.5-flash'
            )->generateContent($prompt);

            $jsonTexto = preg_replace('/```json|```/', '', $geminiResponse->text());
            $datos     = json_decode(trim($jsonTexto), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success'   => false,
                    'mensaje'   => 'Error procesando el RUT',
                    'texto_ocr' => $textoOCR,
                ], 500);
            }

            $imagen = base64_decode($request->imagen);
            $nombre = 'ruts/' . uniqid() . '.jpg';
            Storage::disk('public')->put($nombre, $imagen);

            return response()->json([
                'success'  => true,
                'datos'    => $datos,
                'rut_foto' => $nombre,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmar(Request $request)
    {
        $request->validate([
            'nombre_negocio'       => 'required|string|max:191',
            'nit'                  => 'nullable|string|max:20',
            'nombre_representante' => 'required|string|max:191',
            'email'                => 'required|email',
            'telefono'             => 'nullable|string|max:20',
            'ciudad'               => 'nullable|string|max:100',
            'rut_foto'             => 'nullable|string',
        ]);

        // Verificar si el email ya existe en usuarios
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Este correo ya tiene una cuenta registrada. Inicia sesion en el sistema.',
            ], 422);
        }

        // Verificar si ya tiene una solicitud trial pendiente
        $trialExistente = TrialRequest::where('email', $request->email)
            ->whereIn('estado', ['pendiente', 'activo'])
            ->first();

        if ($trialExistente && $trialExistente->estado === 'activo') {
            return response()->json([
                'success' => false,
                'mensaje' => 'Este correo ya tiene una cuenta activa. Inicia sesion en el sistema.',
            ], 422);
        }

        // Si hay una solicitud pendiente la reutilizamos
        if ($trialExistente && $trialExistente->estado === 'pendiente') {
            $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $trialExistente->update([
                'codigo_verificacion' => $codigo,
                'codigo_expira_at'    => now()->addMinutes(30),
            ]);

            Mail::raw(
                "Hola {$request->nombre_representante},\n\n" .
                "Tu codigo de verificacion para activar el demo de POS Topping es:\n\n" .
                "{$codigo}\n\n" .
                "Este codigo expira en 30 minutos.\n\n" .
                "Si no solicitaste este acceso ignora este mensaje.\n\n" .
                "Avanzas Digital - Tu exito es nuestro objetivo",
                function ($message) use ($request) {
                    $message->to($request->email)
                            ->subject('Codigo de verificacion - POS Topping');
                }
            );

            return response()->json([
                'success'  => true,
                'trial_id' => $trialExistente->id,
                'mensaje'  => 'Codigo enviado a ' . $request->email,
            ]);
        }

        // Crear nueva solicitud trial
        $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $trial = TrialRequest::create([
            'nombre_negocio'       => $request->nombre_negocio,
            'nit'                  => $request->nit,
            'nombre_representante' => $request->nombre_representante,
            'email'                => $request->email,
            'telefono'             => $request->telefono,
            'ciudad'               => $request->ciudad,
            'direccion'            => $request->direccion,
            'rut_foto'             => $request->rut_foto,
            'estado'               => 'pendiente',
            'codigo_verificacion'  => $codigo,
            'codigo_expira_at'     => now()->addMinutes(30),
        ]);

        Mail::raw(
            "Hola {$request->nombre_representante},\n\n" .
            "Tu codigo de verificacion para activar el demo de POS Topping es:\n\n" .
            "{$codigo}\n\n" .
            "Este codigo expira en 30 minutos.\n\n" .
            "Si no solicitaste este acceso ignora este mensaje.\n\n" .
            "Avanzas Digital - Tu exito es nuestro objetivo",
            function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Codigo de verificacion - POS Topping');
            }
        );

        return response()->json([
            'success'  => true,
            'trial_id' => $trial->id,
            'mensaje'  => 'Codigo enviado a ' . $request->email,
        ]);
    }

    public function verificar(Request $request)
    {
        $request->validate([
            'trial_id' => 'required|integer',
            'codigo'   => 'required|string|size:6',
        ]);

        $trial = TrialRequest::findOrFail($request->trial_id);

        if ($trial->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'mensaje' => 'Esta solicitud ya fue procesada',
            ], 422);
        }

        if ($trial->codigo_verificacion !== $request->codigo) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Codigo incorrecto',
            ], 422);
        }

        if (now()->isAfter($trial->codigo_expira_at)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El codigo expiro. Solicita uno nuevo.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Crear tenant
            $tenant = Tenant::create([
                'nombre'              => $trial->nombre_negocio,
                'nit'                 => $trial->nit ?? '000000000-0',
                'telefono'            => $trial->telefono,
                'ciudad'              => $trial->ciudad ?? 'Colombia',
                'plan'                => 'basico',
                'activo'              => true,
                'subscription_status' => 'trial',
                'trial_ends_at'       => now()->addDays(30),
            ]);

            // Crear usuario dueño
            $password = Str::random(10);

            try {
                $user = User::create([
                    'name'      => $trial->nombre_representante,
                    'email'     => $trial->email,
                    'password'  => bcrypt($password),
                    'tenant_id' => $tenant->id,
                    'rol'       => 'dueno',
                    'activo'    => true,
                ]);
            } catch (\Exception $e) {
                // Si el email ya existe en users asignamos ese usuario al nuevo tenant
                $user = User::where('email', $trial->email)->first();
                if ($user) {
                    $user->update([
                        'tenant_id' => $tenant->id,
                        'rol'       => 'dueno',
                        'activo'    => true,
                    ]);
                    $password = '(usa tu contrasena actual)';
                } else {
                    throw $e;
                }
            }

            // Actualizar trial
            $trial->update([
                'estado'        => 'activo',
                'verificado_at' => now(),
                'tenant_id'     => $tenant->id,
            ]);

            DB::commit();

            // Enviar credenciales
            Mail::raw(
                "Bienvenido a POS Topping!\n\n" .
                "Hola {$trial->nombre_representante},\n\n" .
                "Tu cuenta ha sido activada exitosamente. Tienes 30 dias de prueba gratuita.\n\n" .
                "Accede en: https://pos-topping.avanzas.digital/login\n" .
                "Usuario: {$trial->email}\n" .
                "Contrasena temporal: {$password}\n\n" .
                "Te recomendamos cambiar tu contrasena al ingresar por primera vez.\n\n" .
                "Necesitas ayuda? Contactanos en https://www.avanzas.digital\n\n" .
                "Avanzas Digital - Tu exito es nuestro objetivo",
                function ($message) use ($trial) {
                    $message->to($trial->email)
                            ->subject('Bienvenido a POS Topping! - Tus credenciales de acceso');
                }
            );

            return response()->json([
                'success' => true,
                'mensaje' => 'Cuenta activada! Revisa tu correo con las credenciales de acceso.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'mensaje' => 'Error activando la cuenta: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reenviarCodigo(Request $request)
    {
        $request->validate([
            'trial_id' => 'required|integer',
        ]);

        $trial  = TrialRequest::findOrFail($request->trial_id);
        $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $trial->update([
            'codigo_verificacion' => $codigo,
            'codigo_expira_at'    => now()->addMinutes(30),
        ]);

        Mail::raw(
            "Tu nuevo codigo de verificacion es:\n\n{$codigo}\n\nExpira en 30 minutos.\n\nAvanzas Digital",
            function ($message) use ($trial) {
                $message->to($trial->email)
                        ->subject('Nuevo codigo de verificacion - POS Topping');
            }
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Nuevo codigo enviado a ' . $trial->email,
        ]);
    }
}
