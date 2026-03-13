<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Paginated collection of PictureBookResource items.
 *
 * JSON structure: {data: PictureBookResource[], meta: {current_page, last_page, per_page, total}}
 */
class PictureBookCollection extends ResourceCollection
{
    /** @var string */
    public $collects = PictureBookResource::class;

    /**
     * Customize the pagination metadata included in the response.
     *
     * @param mixed $request
     * @param array $paginated
     * @param array $default
     * @return array
     */
    public function paginationInformation($request, $paginated, $default): array
    {
        return [
            'meta' => [
                'current_page' => $paginated['current_page'],
                'last_page' => $paginated['last_page'],
                'per_page' => $paginated['per_page'],
                'total' => $paginated['total'],
            ],
        ];
    }
}
