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

namespace Augias\ClientBundle\Action\Ajax;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Repository\CreditRepository;
use Augias\CoreBundle\Response\AjaxResponse;
use Augias\CoreBundle\Traits\JsonTrait;
use Augias\MoneyBundle\Formatter\MoneyFormatter;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final readonly class Credit implements AjaxResponse
{
    use JsonTrait;

    public function __construct(
        private CreditRepository $repository,
    ) {
    }

    public function get(Client $client): JsonResponse
    {
        return $this->toJson($client);
    }

    /**
     * @throws MathException
     * @throws JsonException
     */
    public function put(Request $request, Client $client): JsonResponse
    {
        $creditInput = (json_decode($request->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR)['credit'] ?? 0);
        $creditInput = is_float($creditInput) ? (string) $creditInput : $creditInput;

        $value = BigNumber::of($creditInput)
            ->toBigDecimal()
            ->multipliedBy(100)
            ->toBigInteger();

        $this->repository->addCredit($client, $value);

        return $this->toJson($client);
    }

    /**
     * @throws MathException
     */
    private function toJson(Client $client): JsonResponse
    {
        return $this->json(
            [
                'credit' => MoneyFormatter::toFloat($client->getCredit()->getValue()),
                'id' => $client->getId(),
            ]
        );
    }
}
