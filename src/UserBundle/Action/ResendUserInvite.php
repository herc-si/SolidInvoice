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

namespace Augias\UserBundle\Action;

use Augias\CoreBundle\Response\FlashResponse;
use Augias\UserBundle\Entity\UserInvitation as UserInvitationEntity;
use Augias\UserBundle\Repository\UserInvitationRepository;
use Augias\UserBundle\UserInvitation\UserInvitation;
use Generator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ResendUserInvite
{
    public function __construct(
        private UserInvitation $invitation,
        private UserInvitationRepository $invitationRepository,
        private RouterInterface $router
    ) {
    }

    public function __invoke(string $id): RedirectResponse
    {
        $invitation = $this->invitationRepository->find(Ulid::fromString($id));

        if ($invitation instanceof UserInvitationEntity) {
            $invitation->renew();
            $this->invitationRepository->save($invitation);

            $this->invitation->sendUserInvitation($invitation);
        }

        $route = $this->router->generate('_users_list');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_SUCCESS => 'users.invitation.success';
            }
        };
    }
}
