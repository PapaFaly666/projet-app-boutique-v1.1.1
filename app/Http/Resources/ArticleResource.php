<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' =>$this->id,
            'libelle' =>$this->libelle,
            'prixUnitaire' =>$this->prixUnitaire,
            'qteStock' =>$this->qteStock,
        ];
    }
}
