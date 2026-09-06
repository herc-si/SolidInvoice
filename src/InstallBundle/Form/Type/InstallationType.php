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

namespace Augias\InstallBundle\Form\Type;

use Augias\AppRequirements;
use Augias\InstallBundle\DTO\Installation;
use Augias\InstallBundle\Form\FormFlow\InstallNavigatorType;
use Augias\InstallBundle\Form\Step\DatabaseConfigStep;
use Augias\InstallBundle\Form\Step\ReviewStep;
use Augias\InstallBundle\Form\Step\StartStep;
use Augias\InstallBundle\Form\Step\SystemRequirementsStep;
use Augias\InstallBundle\Form\Step\UserAccountStep;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Flow\AbstractFlowType;
use Symfony\Component\Form\Flow\DataStorage\SessionDataStorage;
use Symfony\Component\Form\Flow\FormFlowBuilderInterface;
use Symfony\Component\Form\Flow\FormFlowInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class InstallationType extends AbstractFlowType
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly AppRequirements $appRequirements,
        #[Autowire(env: 'AUGIAS_RUNTIME')]
        private readonly ?string $runtime = null,
    ) {
    }

    public function buildFormFlow(FormFlowBuilderInterface $builder, array $options): void
    {
        $builder->addStep(
            name: 'start',
            type: StartStep::class,
            options: [
                'mapped' => false,
            ],
        );
        $builder->addStep(
            name: 'system_requirements',
            type: SystemRequirementsStep::class,
            options: [
                'mapped' => false,
                'constraints' => [
                    new Callback(function (array $data, ExecutionContextInterface $context): void {
                        if (count($this->appRequirements->getFailedRequirements()) > 0) {
                            $context
                                ->buildViolation('install.requirements.not_met')
                                ->atPath('systemRequirements')
                                ->addViolation();
                        }
                    }),
                ],
            ],
            skip: fn () => 'frankenphp' === $this->runtime,
        );
        $builder->addStep(
            'database_config',
            type: DatabaseConfigStep::class,
            options: [
                'property_path' => 'databaseConfig',
                'allow_extra_fields' => true,
            ],
        );
        $builder->addStep(
            name: 'user_account',
            type: UserAccountStep::class,
            options: [
                'property_path' => 'userAccount',
            ],
        );
        $builder->addStep(
            'review',
            ReviewStep::class,
            options: [
                'inherit_data' => true,
            ]
        );
        $builder->addStep('install', options: ['inherit_data' => true]);
        $builder->addStep('finish', options: ['mapped' => false]);

        $builder->add('navigator', InstallNavigatorType::class, ['label' => 'form.field.navigator']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Installation::class,
            'data_storage' => new SessionDataStorage('installation_flow', $this->requestStack),
            'step_property_path' => 'currentStep',
            'validation_groups' => static function (FormFlowInterface $form) {
                $groups = ['Default', $form->getCursor()->getCurrentStep()];

                if (null !== $form->getData()->databaseConfig->driver && $form->getCursor()->getCurrentStep() === 'database_config') {
                    $groups[] = 'database_config_' . $form->getData()->databaseConfig->driver;
                }

                return $groups;
            },
        ]);
    }
}
