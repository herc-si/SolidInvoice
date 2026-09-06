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

namespace Augias\ElectronicInvoicingBundle\Tests\Twig\Components;

use Augias\ElectronicInvoicingBundle\Twig\Components\PendingReceipts;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

#[CoversClass(PendingReceipts::class)]
final class PendingReceiptsTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use InteractsWithTwigComponents;

    /**
     * This panel is the only remaining way into the received-invoices list now
     * that it has no sidebar entry, so it has to render its link even when
     * nothing is waiting — otherwise the page becomes unreachable.
     */
    public function testRendersLinkToReceivedInvoicesWhenNothingIsPending(): void
    {
        $rendered = $this->renderTwigComponent('PendingReceipts')->toString();

        self::assertStringContainsString('/electronic-invoicing/incoming', $rendered);
    }
}
