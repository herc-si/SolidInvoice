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

namespace Augias\CoreBundle\Export;

use Augias\CoreBundle\AugiasCoreBundle;
use Augias\CoreBundle\Entity\ExportJob;
use DateTimeInterface;

final class ManifestGenerator
{
    /**
     * @param array<string, int> $entityCounts
     * @return array<string, mixed>
     */
    public function generate(ExportJob $job, array $entityCounts): array
    {
        return [
            'augias_version' => AugiasCoreBundle::VERSION,
            'export_id' => $job->getId()->toBase58(),
            'company_id' => $job->getCompany()->getId()->toBase58(),
            'requested_by' => $job->getRequestedBy()->toBase58(),
            'requested_at' => $job->getCreatedAt()->format(DateTimeInterface::ATOM),
            'format' => $job->getFormat()->value,
            'entity_counts' => $entityCounts,
        ];
    }
}
