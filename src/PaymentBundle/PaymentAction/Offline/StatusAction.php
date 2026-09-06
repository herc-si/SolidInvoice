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

namespace Augias\PaymentBundle\PaymentAction\Offline;

use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\PaymentAction\Request\StatusRequest;
use Exception;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Offline\Constants;

class StatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Payment $payment */
        $payment = $request->getModel();
        $details = ArrayObject::ensureArrayObject($payment->getDetails() ?: []);

        $details[Constants::FIELD_STATUS] = Constants::STATUS_CAPTURED;

        $payment->setDetails($details);

        try {
            $request->setModel($details);
            $this->gateway->execute($request);

            $payment->setDetails($details);
            $request->setModel($payment);
        } catch (Exception $e) {
            $payment->setDetails($details);
            $request->setModel($payment);

            throw $e;
        }
    }

    public function supports($request)
    {
        return $request instanceof StatusRequest &&
            $request->getModel() instanceof Payment;
    }
}
