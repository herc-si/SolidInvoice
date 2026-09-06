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

namespace Augias\TaxBundle\Action\Grid;

use Augias\CoreBundle\Response\AjaxResponse;
use Augias\CoreBundle\Traits\JsonTrait;
use Augias\TaxBundle\Repository\TaxRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final readonly class Delete implements AjaxResponse
{
    use JsonTrait;

    public function __construct(
        private TaxRepository $repository
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->repository->deleteTaxRates((array) $request->request->get('data'));

        return $this->json([]);
    }
}
