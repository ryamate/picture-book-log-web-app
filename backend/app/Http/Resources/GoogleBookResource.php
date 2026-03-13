<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a Google Books search result.
 *
 * JSON structure: {google_books_id, title, authors, isbn, thumbnail_url, published_date, description, page_count}
 */
class GoogleBookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'google_books_id' => $this['google_books_id'],
            'title' => $this['title'],
            'authors' => $this['authors'],
            'isbn' => $this['isbn'],
            'thumbnail_url' => $this['thumbnail_url'],
            'published_date' => $this['published_date'],
            'description' => $this['description'],
            'page_count' => $this['page_count'],
        ];
    }
}
