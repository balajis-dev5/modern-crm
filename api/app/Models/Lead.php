<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'email', 'phone', 'company', 'source', 'stage', 'deal_value', 'owner_id'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    public const STAGES = ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'];

    public const SOURCES = ['website', 'referral', 'ads', 'cold_call', 'event'];

    protected function casts(): array
    {
        return [
            'deal_value' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(LeadStageHistory::class)->latest('created_at');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function changeStage(string $to, User $by): void
    {
        $from = $this->stage;

        if ($from === $to) {
            return;
        }

        $this->update(['stage' => $to]);

        $this->stageHistories()->create([
            'from_stage' => $from,
            'to_stage' => $to,
            'changed_by' => $by->id,
        ]);
    }
}
