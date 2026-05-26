<?php

declare(strict_types=1);

namespace App\Application\Shared\Criteria;

use App\Shared\Contracts\Criteria\SharedCriterionContract;

/**
 * Trait UtilsCriteria
 *
 * @author Molozhenko
 * @package src/app/Application/Shared/Criteria/UtilsCriteria.php
 * @time 26.05.2026
 */
trait UtilsCriteria
{
    /** @var SharedCriterionContract[] */
    private array $criteria = [];

    /**
     * @return SharedCriterionContract[]
     */
    public function getAndResetCriteria(): array
    {
        $criteria       = $this->criteria;
        $this->criteria = [];

        return $criteria;
    }

    /**
     * @param  SharedCriterionContract  $criterion
     *
     * @return static
     */
    public function append(SharedCriterionContract $criterion): static
    {
        $this->criteria[] = $criterion;

        return $this;
    }
}
