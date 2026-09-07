<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
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
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'amount' => $this->amount,
            'settled_at' => $this->settled_at,
        ];
    }
}
