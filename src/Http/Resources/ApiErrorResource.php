<?php

declare(strict_types=1);

namespace Bolideai\VerifyMicroservice\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiErrorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'status' => $this->resource['status'],
            'message' => $this->resource['message']
        ];
    }

    public function withResponse($request, $response)
    {
        $response->setStatusCode(!empty($this->resource['status_code']) ? $this->resource['status_code'] : 400);
    }
}
