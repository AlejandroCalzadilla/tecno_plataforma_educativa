<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $ventaId = $request->query('venta_id');

        if ($user?->is_propietario) {
            $ventas = Venta::query()
                ->with(['inscripcion.alumno.usuario', 'inscripcion.calendario.servicio'])
                ->orderByDesc('fecha_emision')
                ->paginate(10, ['*'], 'ventas_page')
                ->withQueryString();

            $pagosQuery = Pago::query()
                ->with(['alumno.usuario', 'cuota.venta.inscripcion.calendario.servicio']);

            // Filtrar por venta_id si se proporciona
            if ($ventaId) {
                $pagosQuery->whereHas('cuota.venta', function ($query) use ($ventaId) {
                    $query->where('id', $ventaId);
                });
            }

            $pagos = $pagosQuery
                ->orderByDesc('fecha_pago')
                ->paginate(10, ['*'], 'pagos_page')
                ->withQueryString();

            return Inertia::render('Pagos/IndexPropietario', [
                'ventas' => $ventas,
                'pagos' => $pagos,
                'ventaId' => $ventaId,
            ]);
        }

        if ($user?->is_alumno) {
            $alumno = $user->alumno;

            $pagos = $alumno
                ? $alumno->pagos()
                    ->with(['cuota.venta.inscripcion.calendario.servicio'])
                    ->orderByDesc('fecha_pago')
                    ->paginate(10, ['*'], 'pagos_page')
                    ->withQueryString()
                : Pago::query()->whereRaw('1 = 0')->paginate(10, ['*'], 'pagos_page');

            $cuotas = $alumno
                ? Cuota::query()
                    ->whereHas('venta.inscripcion', function ($query) use ($alumno) {
                        $query->where('id_alumno', $alumno->id);
                    })
                    ->with(['venta.inscripcion.calendario.servicio'])
                    ->orderBy('fecha_vencimiento')
                    ->paginate(10, ['*'], 'cuotas_page')
                    ->withQueryString()
                : Cuota::query()->whereRaw('1 = 0')->paginate(10, ['*'], 'cuotas_page');

            return Inertia::render('Pagos/IndexAlumno', [
                'pagos' => $pagos,
                'cuotas' => $cuotas,
            ]);
        }
        abort(403, 'No tienes permisos para acceder a pagos.');
    }

    public function pagarCuota(Request $request, Cuota $cuota): RedirectResponse
    {
        $user = $request->user();

        if (!$user?->is_alumno || !$user->alumno) {
            abort(403, 'Solo un alumno puede pagar cuotas.');
        }
        $validated = $request->validate([
            'metodo_pago' => 'nullable|in:EFECTIVO,QR,TRANSFERENCIA,TARJETA',
        ]);

        $cuota->load(['venta.inscripcion']);

        $esCuotaDelAlumno = $cuota->venta
            && $cuota->venta->inscripcion
            && (int) $cuota->venta->inscripcion->id_alumno === (int) $user->alumno->id;

        if (!$esCuotaDelAlumno) {
            abort(403, 'No puedes pagar una cuota que no te pertenece.');
        }

        if ($cuota->estado_pago === 'PAGADO') {
            return redirect()->route('pagos.index')->with('warning', 'La cuota ya está pagada.');
        }

        DB::transaction(function () use ($cuota, $user, $validated) {
            Pago::create([
                'id_alumno' => $user->alumno->id,
                'id_cuota' => $cuota->id,
                'fecha_pago' => now(),
                'monto_abonado' => $cuota->monto_cuota,
                'metodo_pago' => $validated['metodo_pago'] ?? 'QR',
            ]);

            $cuota->estado_pago = 'PAGADO';
            $cuota->save();

            $venta = $cuota->venta;
            $nuevoSaldo = max(0, (float) $venta->saldo_pendiente - (float) $cuota->monto_cuota);

            $venta->saldo_pendiente = $nuevoSaldo;
            $venta->estado_financiero = $nuevoSaldo <= 0 ? 'PAGADO' : 'PARCIAL';
            $venta->save();
        });

        return redirect()->route('pagos.index')->with('success', 'Cuota pagada correctamente.');
    }
}