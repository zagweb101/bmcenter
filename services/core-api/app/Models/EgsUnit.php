<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * وحدة توليد الفواتير الإلكترونية (EGS Unit). PRD §16.4.
 * المفاتيح الفعلية في Secrets Vault — هنا مراجع فقط.
 */
class EgsUnit extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'branch_id', 'environment', 'unit_serial', 'common_name',
        'onboarding_status', 'compliance_request_id', 'production_request_id',
        'private_key_ref', 'compliance_csid_ref', 'production_csid_ref',
        'last_icv', 'last_invoice_hash',
    ];

    protected $hidden = ['private_key_ref', 'compliance_csid_ref', 'production_csid_ref'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
