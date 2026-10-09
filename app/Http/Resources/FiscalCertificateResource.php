<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FiscalCertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            'certificate_number' =>
                $this->certificate_number,

            'fingerprint_sha256' =>
                $this->fingerprint_sha256,

            'valid_from' =>
                $this->valid_from,

            'valid_until' =>
                $this->valid_until,

	    'currently_valid' =>
                $this->valid_from !== null &&
                $this->valid_until !== null &&
                now()->betweenIncluded(
                    $this->valid_from,
                    $this->valid_until
                ),

            'status' =>
                $this->status,

            'created_at' =>
                $this->created_at,
        ];
    }
}
