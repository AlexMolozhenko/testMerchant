<?php

declare(strict_types=1);

namespace App\Modules\MerchantApp\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantApp
 *
 * @property int         $id
 * @property int         $merchant_id
 * @property string      $client_id
 * @property string      $client_secret
 * @property string      $name
 * @property string      $mode
 * @property array|null  $permissions
 * @property int|null    $rate_limit_per_minute
 * @property string      $status
 * @property string|null $last_used_at
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @author Molozhenko
 * @package src/app/Modules/MerchantApp/Models/MerchantApp.php
 * @time 26.05.2026
 */
final class MerchantApp extends Model
{
    protected $table = 'merchant_apps';

    protected $casts = [
        'permissions' => 'array',
    ];
}
