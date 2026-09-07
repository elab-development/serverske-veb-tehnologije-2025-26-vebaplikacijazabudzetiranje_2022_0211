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
            'group_id' => $this->group_id,
            'category_id' => $this->category_id,
            'paid_by' => $this->paid_by,
            'amount' => $this->amount,
            'description' => $this->description,
            'payment_date' => $this->payment_date,
            'created_at' => $this->created_at,
        ];
    }
}
