<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'title',
        'file_path',
        'file_type',
        'is_kyc',
        'meta',
        'documentable_id',
        'documentable_type',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function isImage(): bool
    {
        $ext = strtolower($this->file_type ?: pathinfo($this->file_path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'jfif', 'bmp']);
    }

    public function isPdf(): bool
    {
        $ext = strtolower($this->file_type ?: pathinfo($this->file_path, PATHINFO_EXTENSION));
        return $ext === 'pdf';
    }

    public function getMetaArrayAttribute(): array
    {
        if (empty($this->meta)) return [];
        if (is_array($this->meta)) return $this->meta;
        $decoded = json_decode((string)$this->meta, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getCategoryBadgeAttribute(): array
    {
        if ($this->is_kyc) {
            $field = $this->meta_array['field'] ?? '';
            $label = $field ? strtoupper(str_replace('_', ' ', $field)) : 'KYC DOCUMENT';
            return ['label' => $label, 'class' => 'bg-warning text-dark border-warning'];
        }

        $source = $this->meta_array['source'] ?? '';
        if ($source === 'policy_pdf_history' || $source === 'policies_table' || ($this->documentable_type && str_contains($this->documentable_type, 'Policy'))) {
            return ['label' => 'POLICY PDF', 'class' => 'bg-info text-dark border-info'];
        }

        if ($this->isImage()) {
            return ['label' => 'IMAGE', 'class' => 'bg-success text-white border-success'];
        }

        return ['label' => strtoupper($this->file_type ?: 'DOCUMENT'), 'class' => 'bg-secondary text-white border-secondary'];
    }
}
