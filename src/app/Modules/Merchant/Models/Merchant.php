<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Merchant
 *
 * @property int $id
 * @property int $user_id
 * @property string $merchant_uuid
 * @property string $business_name
 * @property string $site_url
 * @property string|null $logo
 * @property string $status
 *
 * @author Molozhenko
 * @package src/app/Modules/Merchant/Models/Merchant.php
 * @time 26.05.2026
 */
final class Merchant extends Model
{
    protected $table = 'merchants';

    public $timestamps = false;

    protected $fillable = [];
}
