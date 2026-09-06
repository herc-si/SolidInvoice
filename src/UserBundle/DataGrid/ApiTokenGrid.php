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

namespace Augias\UserBundle\DataGrid;

use Augias\DataGridBundle\Attributes\AsDataGrid;
use Augias\DataGridBundle\Grid;
use Augias\DataGridBundle\GridBuilder\Action\Action;
use Augias\DataGridBundle\GridBuilder\Batch\BatchAction;
use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Augias\DataGridBundle\GridBuilder\Query;
use Augias\DataGridBundle\Source\ORMSource;
use Augias\UserBundle\Entity\ApiToken;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use SensitiveParameter;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @see \Augias\UserBundle\Tests\DataGrid\ApiTokenGridTest
 */
#[AsDataGrid(name: 'api_token_grid', title: 'API Tokens')]
final class ApiTokenGrid extends Grid
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function entityFQCN(): string
    {
        return ApiToken::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('name')
                ->label('user.api_token.grid.name')
                ->searchable(true)
                ->sortable(true),
            StringColumn::new('description')
                ->label('user.api_token.grid.description')
                ->searchable(true)
                ->sortable(false)
                ->formatValue(static fn (?string $value) => $value ? (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) : '-'),
            StringColumn::new('usageCount')
                ->label('user.api_token.grid.usage_count')
                ->searchable(false)
                ->sortable(false)
                ->formatValue(static fn ($value, #[SensitiveParameter] ApiToken $token) => $token->getUsageCount()),
            RelativeDateColumn::new('lastUsed')
                ->label('user.api_token.grid.last_used')
                ->searchable(false)
                ->sortable(false)
                ->formatValue(static function ($value, #[SensitiveParameter] ApiToken $token) {
                    $history = $token->getHistory();
                    return $history->count() > 0 ? $history->first()->getCreated() : null;
                }),
            RelativeDateColumn::new('created')
                ->label('user.api_token.grid.created')
                ->sortable(true),
        ];
    }

    /**
     * @return Action[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            Action::new('_api_keys_index', ['view_history' => 'id'])
                ->label('View History')
                ->icon('history')
                ->inMenu(),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        yield BatchAction::new('Revoke')
            ->icon('ban')
            ->color('danger')
            ->action(function (ApiTokenRepository $repository, array $selectedItems): void {
                $currentUser = $this->security->getUser();

                if (! $currentUser instanceof User) {
                    return;
                }

                foreach ($selectedItems as $tokenId) {
                    $token = $repository->find($tokenId);
                    if ($token instanceof ApiToken) {
                        $tokenUser = $token->getUser();
                        assert($tokenUser instanceof User);
                        if ($tokenUser->getId() === $currentUser->getId()) {
                            $repository->revoke($token);
                        }
                    }
                }
            });
    }

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $user = $this->security->getUser();

        assert($user instanceof User);

        $query->getQueryBuilder()
            ->leftJoin(ORMSource::ALIAS . '.history', 'h')
            ->addSelect('h')
            ->where(ORMSource::ALIAS . '.user = :user')
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->orderBy(ORMSource::ALIAS . '.created', 'DESC');

        return $query;
    }

    #[Override]
    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('Create API Token');
    }
}
