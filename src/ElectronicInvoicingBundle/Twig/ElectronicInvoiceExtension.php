<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ElectronicInvoicingBundle\Twig;

use Override;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceSubmission;
use SolidInvoice\ElectronicInvoicingBundle\Enum\ElectronicInvoiceProcessingStatus;
use SolidInvoice\ElectronicInvoicingBundle\Provider\ElectronicInvoiceProviderRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ElectronicInvoiceExtension extends AbstractExtension
{
    public function __construct(
        private readonly ElectronicInvoiceProviderRegistry $registry,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('einvoicing_processing_status', $this->resolveProcessingStatus(...)),
        ];
    }

    /**
     * Falls back to the submission's own success flag if its provider is no
     * longer configured (e.g. it was removed after the submission was sent) —
     * there is no provider left to classify a status code into Accepted, but
     * an outright failed submission is still known to be Rejected.
     */
    public function resolveProcessingStatus(ElectronicInvoiceSubmission $submission): ElectronicInvoiceProcessingStatus
    {
        $provider = $this->registry->get($submission->getProvider());

        if ($provider !== null) {
            return $provider->resolveProcessingStatus($submission);
        }

        return $submission->isSuccess() ? ElectronicInvoiceProcessingStatus::Pending : ElectronicInvoiceProcessingStatus::Rejected;
    }
}
