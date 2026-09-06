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

namespace Augias\PaymentBundle\PaymentAction;

use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Enum\PaymentStatus;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Offline\Constants;

class CaptureAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Payment $payment */
        $payment = $request->getModel();
        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $details[Constants::FIELD_STATUS] = PaymentStatus::New->value;
        $payment->setDetails($details);

        $request->setModel($details);

        $this->gateway->execute($request);
    }

    public function supports($request)
    {
        if (! $request instanceof Capture || ! $request->getModel() instanceof Payment) {
            return false;
        }

        $details = ArrayObject::ensureArrayObject($request->getModel()->getDetails());

        return null === $details[Constants::FIELD_STATUS];
    }
}
