<?php

namespace App\Http\Resources;

use App\Support\NullToEmptyArray;
use Illuminate\Http\Resources\Json\PaginatedResourceResponse;

class ApiPaginatedResourceResponse extends PaginatedResourceResponse
{
    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request)
    {
        $response = parent::toResponse($request);

        /** @var array<string, mixed> $data */
        $data = $response->getData(true);

        $response->setData(NullToEmptyArray::convertPaginationEnvelope($data));

        return $response;
    }
}
