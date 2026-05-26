<?php

declare(strict_types=1);

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class User
 *
 * @property int $id
 * @property string $email
 * @property bool $sub_verified
 * @property bool $identity_verified
 * @property string|null $applicant_id
 * @property string|null $google2fa_secret
 * @property Carbon $created_at
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Models/User.php
 * @time 26.05.2026
 */
final class User extends Model
{
    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'sub_verified'      => 'boolean',
            'identity_verified' => 'boolean',
            'created_at'        => 'datetime',
        ];
    }
}
