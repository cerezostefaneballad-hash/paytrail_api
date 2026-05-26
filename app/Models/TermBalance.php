<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermBalance extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year',
        'semester',
        'total_payable',
        'total_paid',
        'payment_breakdown',
        'status',
        // outstanding_balance is a stored computed column — never fill it directly
    ];

    protected $casts = [
        'payment_breakdown'   => 'array',
        'total_payable'       => 'decimal:2',
        'total_paid'          => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Recompute and persist the status based on current totals.
     * Call this after any change to total_paid or total_payable.
     */
    public function updateStatus(): void
    {
        if ((float) $this->total_paid <= 0) {
            $this->status = 'Unpaid';
        } elseif ((float) $this->total_paid >= (float) $this->total_payable) {
            $this->status = 'Fully Paid';
        } else {
            $this->status = 'Partial';
        }
        $this->saveQuietly();
    }

    /**
     * Convenience: whether this term record has a carry-over entry
     * in its payment_breakdown.
     */
    public function getHasCarryOverAttribute(): bool
    {
        if (!is_array($this->payment_breakdown)) return false;
        return collect($this->payment_breakdown)
            ->contains('type', 'carry_over');
    }

    /**
     * The carry-over amount from the previous term, or 0 if none.
     */
    public function getCarriedAmountAttribute(): float
    {
        if (!is_array($this->payment_breakdown)) return 0.0;
        $entry = collect($this->payment_breakdown)
            ->firstWhere('type', 'carry_over');
        return $entry ? (float) ($entry['carried_amount'] ?? 0) : 0.0;
    }
}