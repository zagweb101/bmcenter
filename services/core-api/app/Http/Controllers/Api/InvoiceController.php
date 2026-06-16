<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API الفواتير. PRD §8.3, §16, §17.
 * الإصدار الفعلي المتوافق مع ZATCA يُضاف لاحقًا؛ هنا توليد المسودة من التسجيل.
 */
class InvoiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Invoice::query()->latest();
        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        return InvoiceResource::collection($query->paginate(20));
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice);
    }

    /**
     * رصيد الفاتورة المتبقّي. PRD §17.
     */
    public function balance(Invoice $invoice): JsonResponse
    {
        return response()->json([
            'invoice_id' => $invoice->id,
            'total_including_tax' => $invoice->total_including_tax,
            'allocated' => $invoice->allocatedTotal(),
            'outstanding' => $invoice->outstanding(),
        ]);
    }

    /**
     * توليد فاتورة Draft من تسجيل. PRD §8.3.
     */
    public function storeFromEnrollment(Enrollment $enrollment, InvoiceService $service): JsonResponse
    {
        $invoice = $service->createDraftFromEnrollment($enrollment);

        return (new InvoiceResource($invoice))->response()->setStatusCode(201);
    }

    /**
     * إصدار الفاتورة (Draft → Issued). PRD §16.5.
     */
    public function issue(Invoice $invoice, InvoiceService $service): InvoiceResource
    {
        return new InvoiceResource($service->issue($invoice));
    }

    /**
     * إشعار دائن. PRD §16.1, §17.
     */
    public function creditNote(Request $request, Invoice $invoice, InvoiceService $service): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $note = $service->createNote($invoice, 'credit', (string) $data['amount'], $data['reason'] ?? null);

        return (new InvoiceResource($note))->response()->setStatusCode(201);
    }

    /**
     * إشعار مدين. PRD §16.1, §17.
     */
    public function debitNote(Request $request, Invoice $invoice, InvoiceService $service): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $note = $service->createNote($invoice, 'debit', (string) $data['amount'], $data['reason'] ?? null);

        return (new InvoiceResource($note))->response()->setStatusCode(201);
    }
}
