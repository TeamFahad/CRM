<?php

namespace App\Modules\CRM\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** @property string $id */
class Lead extends Model
{
    use BelongsToTenant, HasUlids;

    protected $fillable = ['name', 'email', 'phone', 'source', 'status', 'notes', 'created_by'];
}
