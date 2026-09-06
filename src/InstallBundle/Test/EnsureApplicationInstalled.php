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

namespace Augias\InstallBundle\Test;

use Augias\CoreBundle\AugiasCoreBundle;
use Augias\CoreBundle\Company\CompanySelector;
use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Test\Factory\CompanyFactory;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use function date;
use function putenv;

trait EnsureApplicationInstalled
{
    protected Company $company;

    #[Before]
    public function createCompany(): void
    {
        $_SERVER['AUGIAS_LOCALE'] = $_ENV['AUGIAS_LOCALE'] = 'en_US';
        $_SERVER['AUGIAS_INSTALLED'] = $_ENV['AUGIAS_INSTALLED'] = date(DateTimeInterface::ATOM);
        putenv('AUGIAS_INSTALLED=' . $_SERVER['AUGIAS_INSTALLED']);

        $this->company = CompanyFactory::createOne(['name' => AugiasCoreBundle::APP_NAME]);

        static::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
    }

    #[After]
    public function resetCompany(): void
    {
        unset(
            $_SERVER['AUGIAS_LOCALE'],
            $_ENV['AUGIAS_LOCALE'],
            $_SERVER['AUGIAS_INSTALLED'],
            $_ENV['AUGIAS_INSTALLED'],
            $this->company
        );
        // No trailing "=": putenv('VAR=') keeps the variable with an empty value, which
        // installation-state checks read as "present". Only putenv('VAR') removes it.
        putenv('AUGIAS_INSTALLED');
    }
}
