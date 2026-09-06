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

namespace Augias\UserBundle\Tests\Functional;

use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\UserBundle\Test\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[Group('functional')]
final class LoginTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testRedirectToLoginPage(): void
    {
        UserFactory::createOne(['companies' => [$this->company]]);

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->followRedirects();

        $crawler = $client->request(Request::METHOD_GET, '/');
        self::assertStringContainsString('/login', (string) $crawler->getUri());
    }
}
