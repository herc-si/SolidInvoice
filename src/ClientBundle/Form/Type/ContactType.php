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

namespace Augias\ClientBundle\Form\Type;

use Augias\ClientBundle\Entity\Contact;
use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use Augias\SaasBundle\Feature\Feature;
use Override;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \Augias\ClientBundle\Tests\Form\Type\ContactTypeTest
 * @extends AbstractType<Contact>
 */
class ContactType extends AbstractType
{
    public function __construct(
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('firstName', null, ['label' => 'client.contact.firstName.label', 'sanitize_html' => true, 'allow_single_quotes' => true]);
        $builder->add('lastName', null, ['label' => 'client.contact.lastName.label', 'sanitize_html' => true, 'allow_single_quotes' => true]);
        $builder->add('email', null, ['label' => 'client.contact.email.label']);

        if ($this->featureGate->isEnabled(Feature::CustomFields->value)) {
            $builder->add('customFields', CustomFieldValueCollectionType::class, [
                'target' => CustomFieldTarget::CONTACT,
                'parent_record' => $options['data'] ?? null,
                'manage_persistence' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Contact::class);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'contact';
    }
}
