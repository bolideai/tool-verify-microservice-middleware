<?php

namespace Bolideai\VerifyMicroservice\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiErrorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'status' => $this->resource['status'],
            'message' => $this->resource['message']
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withResponse($request, $response)
    {
        $response->setStatusCode(!empty($this->resource['status_code']) ? $this->resource['status_code'] : 400);
    }
}
