<?php

namespace App\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $locale
 */
class Tenant extends Model
{
    use HasUlids;

    protected $fillable = ['name', 'industry', 'locale', 'timezone', 'currency'];
}
