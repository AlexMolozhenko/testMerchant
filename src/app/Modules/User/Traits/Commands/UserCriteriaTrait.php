<?php

declare(strict_types=1);

namespace App\Modules\User\Traits\Commands;

use App\Application\Shared\Criteria\UtilsCriteria;
use App\Modules\User\Criteria\UserByEmailCriteria;
use App\Modules\User\Criteria\UserByIdCriteria;

/**
 * Trait UserCriteriaTrait
 *
 * @author Molozhenko
 * @package src/app/Modules/User/Traits/Commands/UserCriteriaTrait.php
 * @time 26.05.2026
 */
trait UserCriteriaTrait
{
    use UtilsCriteria;

    /**
     * @param  int  $id
     *
     * @return $this
     */
    final public function byId(int $id): static
    {
        $this->criteria[] = new UserByIdCriteria($id);

        return $this;
    }

    /**
     * @param  string  $email
     *
     * @return $this
     */
    final public function byEmail(string $email): static
    {
        $this->criteria[] = new UserByEmailCriteria($email);

        return $this;
    }
}
