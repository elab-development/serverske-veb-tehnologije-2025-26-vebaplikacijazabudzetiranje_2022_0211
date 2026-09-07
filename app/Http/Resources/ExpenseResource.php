<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'description' => $this->description,
            'payment_date' => $this->payment_date,

            'group' => new GroupResource(
                $this->whenLoaded('group')
            ),

            'category' => new CategoryResource(
                $this->whenLoaded('category')
            ),

            'payer' => new UserResource(
                $this->whenLoaded('payer')
            ),

            'shares' => ExpenseShareResource::collection(
                $this->whenLoaded('shares')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
