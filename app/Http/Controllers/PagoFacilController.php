<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Pago;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagoFacilController extends Controller
{
    // Códigos de estado que devuelve PagoFácil
    private const STATUS_COMPLETED = 2;
    private const STATUS_REJECTED  = 3;

    // ----------------------------------------------------------------
    // Generar QR para pagar una cuota
    // ----------------------------------------------------------------

    /**
     * Recibe { id_cuota } y devuelve la imagen QR + transactionId.
     * No escribe nada en la BD; el registro definitivo ocurre al confirmar.
     */
    public function generarQR(Request $request): JsonResponse
    {
        $request->validate([
            'id_cuota' => 'required|exists:cuota,id',
        ]);

        /** @var \App\Models\Cuota $cuota */
        $cuota = Cuota::with(['venta.inscripcion.calendario.servicio', 'venta.inscripcion.alumno.usuario'])
            ->findOrFail($request->id_cuota);

        // Validar que la cuota pertenece al alumno autenticado
        if ($cuota->id_alumno !== optional(auth()->user()->alumno)->id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso sobre esta cuota'], 403);
        }

        if ($cuota->estaPagada()) {
            return response()->json(['success' => false, 'message' => 'Esta cuota ya está pagada'], 400);
        }

        $servicio    = $cuota->venta->inscripcion->calendario->servicio;
        $nroPago     = "cuota-{$cuota->id}-" . time();   // referencia para el callback
        $monto       = (float) $cuota->monto_cuota;
        $usuario     = auth()->user();

        try {
            $accessToken = $this->obtenerToken();

            $client   = new Client();
            $response = $client->post(config('pagofacil.base_url') . '/generate-qr', [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'json' => [
                    'paymentMethod' => 4,                   // 4 = QR
                    'clientName'    => $usuario->name,
                    'documentType'  => 1,
                    'documentId'    => (string) ($request->ci_nit ?? '0'),
                    'phoneNumber'   => (string) ($request->telefono ?? '0'),
                    'email'         => $usuario->email,
                    'paymentNumber' => $nroPago,
                    'amount'        => $monto,
                    'currency'      => config('pagofacil.currency', 2), // 2 = BOB
                    'clientCode'    => (string) $usuario->id,
                    'callbackUrl'   => config('pagofacil.callback_url'),
                    'orderDetail'   => $this->buildOrderDetail($servicio->nombre, $monto),
                ],
                'timeout' => config('pagofacil.timeout', 30),
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Log completo para diagnosticar keys reales de PagoFácil
            Log::info('PagoFacil generarQR response', ['result' => $result]);

            if (json_last_error() !== JSON_ERROR_NONE || ! isset($result['values'])) {
                return response()->json(['success' => false, 'message' => 'Respuesta inválida de PagoFácil', 'debug' => $result], 500);
            }

            $values = $result['values'];

            // PagoFácil puede devolver el transactionId con distintos nombres según versión
            $qrBase64 = $values['qrImage'] ?? $values['qrBase64'] ?? null;
            $transactionId = $values['transactionId']
                ?? $values['idTransaccion']
                ?? $values['codigoTransaccion']
                ?? $values['id']
                ?? null;

            if (! $qrBase64 || ! $transactionId) {
                Log::error('PagoFacil generarQR: faltan campos', ['values' => $values]);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el QR. Campos recibidos: ' . implode(', ', array_keys($values)),
                    'debug'   => $values,
                ], 500);
            }

            return response()->json([
                'success'        => true,
                'qr_image'       => 'data:image/png;base64,' . $qrBase64,
                'transaction_id' => $transactionId,
                'nro_pago'       => $nroPago,
                'monto'          => $monto,
            ]);

        } catch (\Throwable $th) {
            if (config('pagofacil.enable_logs')) {
                Log::error('PagoFacil generarQR error', ['msg' => $th->getMessage()]);
            }
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // Verificar estado de cuota en nuestra BD (polling principal)
    // ----------------------------------------------------------------

    /**
     * Consulta si la cuota ya fue marcada PAGADO en nuestra BD.
     * El callback de PagoFácil actualiza la cuota; este endpoint permite
     * que el frontend lo detecte sin depender del transactionId externo.
     */
    public function verificarCuota(Request $request): JsonResponse
    {
        $request->validate([
            'id_cuota' => 'required|exists:cuota,id',
        ]);

        // Liberar el bloqueo de sesión de PHP inmediatamente.
        // Sin esto, las peticiones de polling bloquean el servidor (php artisan serve)
        // e impiden que el callback de PagoFácil se procese hasta que el modal se cierre.
        session()->save();

        $cuota = Cuota::findOrFail($request->id_cuota);

        if ($cuota->id_alumno !== optional(auth()->user()->alumno)->id) {
            return response()->json(['success' => false, 'message' => 'Sin permiso'], 403);
        }

        return response()->json([
            'success'      => true,
            'estado_pago'  => $cuota->estado_pago,
            'pagada'       => $cuota->estaPagada(),
        ]);
    }

    // ----------------------------------------------------------------
    // Consultar estado de una transacción
    // ----------------------------------------------------------------

    /**
     * El frontend hace polling a este endpoint después de mostrar el QR.
     * Recibe { transaction_id } y devuelve el estado desde PagoFácil.
     */
    public function consultarEstado(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        // Liberar sesión antes de la llamada externa para no bloquear otras peticiones
        session()->save();

        set_time_limit(120);

        try {
            $accessToken = $this->obtenerToken();

            $client   = new Client();
            $response = $client->post(config('pagofacil.base_url') . '/query-transaction', [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'json'         => ['pagofacilTransactionId' => $request->transaction_id],
                'http_errors'  => false,
                'timeout'      => 90,
                'connect_timeout' => 10,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['success' => false, 'message' => 'Respuesta inválida del proveedor'], 500);
            }

            if (isset($result['error']) && $result['error'] != 0) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Error en la transacción',
                ], 400);
            }

            if (! isset($result['values'])) {
                return response()->json(['success' => false, 'message' => 'Datos no encontrados'], 404);
            }

            $values = $result['values'];

            return response()->json([
                'success' => true,
                'data'    => [
                    'pagofacilTransactionId'     => $values['pagofacilTransactionId']     ?? null,
                    'companyTransactionId'        => $values['companyTransactionId']        ?? null,
                    'paymentStatus'              => $values['paymentStatus']              ?? null,
                    'paymentStatusDescription'   => $values['paymentStatusDescription']   ?? '',
                    'paymentDate'                => $values['paymentDate']                ?? null,
                    'paymentTime'                => $values['paymentTime']                ?? null,
                ],
                'message' => $result['message'] ?? 'Consulta realizada',
            ]);

        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // Confirmar el pago desde el frontend (fallback al callback)
    // ----------------------------------------------------------------

    /**
     * El frontend llama a este endpoint cuando el polling confirma el pago.
     * Recibe { id_cuota, transaction_id, nro_pago }.
     * Crea el Pago y actualiza Cuota + Venta.
     */
    public function confirmarPagoCuota(Request $request): JsonResponse
    {
        $request->validate([
            'id_cuota'       => 'required|exists:cuota,id',
            'transaction_id' => 'required|string',
            'nro_pago'       => 'required|string',
        ]);

        /** @var \App\Models\Cuota $cuota */
        $cuota = Cuota::findOrFail($request->id_cuota);

        if ($cuota->id_alumno !== optional(auth()->user()->alumno)->id) {
            return response()->json(['success' => false, 'message' => 'Sin permiso'], 403);
        }

        if ($cuota->estaPagada()) {
            return response()->json(['success' => true, 'message' => 'Cuota ya registrada como pagada']);
        }

        // Idempotencia: si ya existe un pago con ese transaction_id no duplicar
        if (Pago::buscarPorReferencia($request->transaction_id)) {
            $cuota->update(['estado_pago' => 'PAGADO']);
            $cuota->venta->recalcularSaldo();
            return response()->json(['success' => true, 'message' => 'Pago ya registrado']);
        }

        $cuota->confirmarPago(
            montoAbonado:      (float) $cuota->monto_cuota,
            metodoPago:        'QR',
            codigoTransaccion: $request->nro_pago . '|' . $request->transaction_id,
        );

        return response()->json(['success' => true, 'message' => 'Pago confirmado correctamente']);
    }

    // ----------------------------------------------------------------
    // Callback de PagoFácil (webhook, sin auth)
    // ----------------------------------------------------------------

    /**
     * PagoFácil llama a esta URL cuando completa/rechaza la transacción.
     * Busca el pago por el nroPago (PedidoID) que tiene el cuota id embebido.
     */
    public function callback(Request $request): JsonResponse
    {
        $pedidoId  = $request->input('PedidoID');   // nroPago que enviamos: "cuota-{id}-{ts}"
        $estado    = $request->input('Estado');

        if (! $pedidoId) {
            return response()->json(['error' => 1, 'status' => 0, 'message' => 'PedidoID requerido', 'values' => false]);
        }

        if (config('pagofacil.enable_logs')) {
            Log::info('PagoFacil callback', $request->all());
        }

        // Extraer id_cuota del nroPago: "cuota-{id}-{timestamp}"
        $cuotaId = null;
        if (preg_match('/^cuota-(\d+)-/', $pedidoId, $matches)) {
            $cuotaId = (int) $matches[1];
        }

        if (! $cuotaId) {
            return response()->json(['error' => 1, 'status' => 0, 'message' => 'Referencia de cuota no válida', 'values' => false]);
        }

        /** @var \App\Models\Cuota|null $cuota */
        $cuota = Cuota::find($cuotaId);

        if (! $cuota) {
            return response()->json(['error' => 1, 'status' => 0, 'message' => 'Cuota no encontrada', 'values' => false]);
        }

        $estadoNormalizado = $this->normalizarEstadoPagoFacil($estado);

        if ($estadoNormalizado === 'PAGADO' && ! $cuota->estaPagada()) {
            // Idempotencia: no crear pago duplicado
            if (! Pago::buscarPorReferencia($pedidoId)) {
                $cuota->confirmarPago(
                    montoAbonado:      (float) $cuota->monto_cuota,
                    metodoPago:        'QR',
                    codigoTransaccion: $pedidoId . '|callback|' . $request->input('TransactionID', ''),
                );
            }
        }

        return response()->json(['error' => 0, 'status' => 1, 'message' => 'Procesado', 'values' => true]);
    }

    // ----------------------------------------------------------------
    // Helpers privados
    // ----------------------------------------------------------------

    /**
     * Obtiene el accessToken de PagoFácil.
     *
     * @throws \Exception si no se puede autenticar
     */
    private function obtenerToken(): string
    {
        $client   = new Client();
        $response = $client->post(config('pagofacil.base_url') . '/login', [
            'headers' => [
                'Accept'         => 'application/json',
                'tcTokenService' => config('pagofacil.token_service'),
                'tcTokenSecret'  => config('pagofacil.token_secret'),
            ],
            'timeout' => config('pagofacil.timeout', 30),
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (config('pagofacil.enable_logs')) {
            Log::info('PagoFacil token OK');
        }

        $token = $result['values']['accessToken'] ?? null;

        if (! $token) {
            throw new \Exception('No se pudo obtener el token de PagoFácil');
        }

        return $token;
    }

    /**
     * Construye el array orderDetail mínimo para PagoFácil.
     */
    private function buildOrderDetail(string $nombreServicio, float $monto): array
    {
        return [
            [
                'serial'   => 1,
                'product'  => $nombreServicio,
                'quantity' => 1,
                'price'    => $monto,
                'discount' => 0,
                'total'    => $monto,
            ],
        ];
    }

    /**
     * Convierte el estado recibido desde PagoFácil a nuestro enum interno.
     * Retorna 'PAGADO', 'CANCELADO' o 'PENDIENTE'.
     */
    private function normalizarEstadoPagoFacil(mixed $estado): string
    {
        $lower = strtolower((string) $estado);

        if (
            in_array($estado, [1, '1', self::STATUS_COMPLETED], true) ||
            str_contains($lower, 'complet') ||
            str_contains($lower, 'procesado') ||
            str_contains($lower, 'pagado')
        ) {
            return 'PAGADO';
        }

        if (
            in_array($estado, [3, '3', self::STATUS_REJECTED], true) ||
            str_contains($lower, 'rechaz') ||
            str_contains($lower, 'cancel')
        ) {
            return 'CANCELADO';
        }

        return 'PENDIENTE';
    }
}
