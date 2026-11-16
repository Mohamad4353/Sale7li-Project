<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Payment;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves user-uploaded files from the private `local` disk only after an
 * authorization check against the owning resource. Uploads never live on the
 * public disk, so there is no predictable public URL for them.
 */
class AttachmentController extends Controller
{
    public function requestImage(ServiceRequest $serviceRequest, string $collection, int $index): StreamedResponse
    {
        $this->authorize('view', $serviceRequest);

        abort_unless(in_array($collection, ['problem', 'completion'], true), 404);

        $paths = $collection === 'problem'
            ? ($serviceRequest->problem_images ?? [])
            : ($serviceRequest->completion_images ?? []);

        return $this->stream($paths[$index] ?? null);
    }

    public function messageAttachment(Message $message, int $index): StreamedResponse
    {
        $this->authorize('view', $message);

        return $this->stream(($message->attachments ?? [])[$index] ?? null);
    }

    /**
     * WHISH payment proof. Visible only to the paying customer or an admin
     * (PaymentPolicy::view) — never the assigned worker, never a stranger.
     */
    public function paymentProof(Payment $payment): StreamedResponse
    {
        $this->authorize('view', $payment);

        return $this->stream($payment->payment_proof_path);
    }

    private function stream(?string $path): StreamedResponse
    {
        abort_if($path === null, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
