<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\AccountingBundle\Action;

use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Service\AccountingProfileProvider;
use Symfony\Bridge\Twig\Attribute\Template;

/**
 * The accounting home page.
 *
 * Until a regime is chosen there is nothing meaningful to show — an empty book
 * measured against limits the user never picked would be worse than no page at
 * all — so this hands the template the profile and lets it render either the
 * setup prompt or the books.
 */
final readonly class Index
{
    public function __construct(
        private AccountingProfileProvider $profileProvider,
        private RegimeRegistry $registry,
    ) {
    }

    /**
     * @return array{profile: AccountingProfile, regime: RegimeInterface|null}
     */
    #[Template('@AugiasAccounting/Default/index.html.twig')]
    public function __invoke(): array
    {
        $profile = $this->profileProvider->forCompany();

        return [
            'profile' => $profile,
            'regime' => $this->registry->forProfile($profile),
        ];
    }
}
