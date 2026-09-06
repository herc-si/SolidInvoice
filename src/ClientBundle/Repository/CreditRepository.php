<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\ClientBundle\Repository;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Entity\Credit;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use function assert;

/**
 * @extends EntityRepository<Credit>
 */
class CreditRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Credit::class);
    }

    /**
     * @throws MathException
     */
    public function addCredit(Client $client, BigNumber | float | int | string $amount): Credit
    {
        $credit = $client->getCredit();

        $value = $credit->getValue();
        assert($value instanceof BigInteger || $value instanceof BigDecimal);

        $credit->setValue($value->plus($amount));

        $this->save($credit);

        return $credit;
    }

    /**
     * @throws MathException
     */
    public function deductCredit(Client $client, BigNumber | float | int | string $amount): Credit
    {
        $credit = $client->getCredit();

        $value = $credit->getValue();
        assert($value instanceof BigInteger || $value instanceof BigDecimal);

        $credit->setValue($value->minus($amount));

        $this->save($credit);

        return $credit;
    }
}
