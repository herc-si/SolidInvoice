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

namespace Augias\CoreBundle\Serializer\Normalizer;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Entity\Contact;
use Augias\CoreBundle\Company\CompanySelectorInterface;
use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Repository\CustomFieldRepository;
use Augias\CoreBundle\Repository\CustomFieldValueRepository;
use Augias\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\RecurringInvoice;
use Augias\QuoteBundle\Entity\Quote;
use Augias\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Service\ResetInterface;

#[AutoconfigureTag('serializer.normalizer')]
#[AutoconfigureTag('kernel.reset', ['method' => 'reset'])]
final class CustomFieldsNormalizer implements NormalizerAwareInterface, NormalizerInterface, ResetInterface
{
    use NormalizerAwareTrait;

    private const string SKIP_KEY = self::class . '::skip';

    /**
     * Per-request cache of field definitions keyed by target value. There are
     * only four target values (client/contact/invoice/quote) and a paginated
     * GET runs this normalizer once per item, so caching saves N-1 queries
     * per page. Cleared via {@see reset()} between requests in long-running
     * processes (FrankenPHP / worker mode) to avoid leaking stale defs.
     *
     * @var array<string, list<CustomField>>
     */
    private array $defsByTarget = [];

    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
        private readonly CustomFieldTypeResolver $resolver,
        private readonly FeatureGate $featureGate,
        private readonly CompanySelectorInterface $companySelector,
    ) {
    }

    public function reset(): void
    {
        $this->defsByTarget = [];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $context[self::SKIP_KEY] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        if (! is_array($data)) {
            return ['customFields' => (object) []];
        }

        $target = match (true) {
            $object instanceof Client => CustomFieldTarget::CLIENT,
            $object instanceof Contact => CustomFieldTarget::CONTACT,
            $object instanceof Invoice, $object instanceof RecurringInvoice => CustomFieldTarget::INVOICE,
            $object instanceof Quote => CustomFieldTarget::QUOTE,
            default => CustomFieldTarget::CONTACT,
        };
        $companyId = $this->companySelector->getCompany();
        if (! $companyId instanceof Ulid) {
            $data['customFields'] = (object) [];
            return $data;
        }

        $cacheKey = $target->value . ':' . $companyId->toBase32();
        $defs = $this->defsByTarget[$cacheKey] ??= $this->fields->findByTargetAndCompany($target, $companyId);

        if ($defs === [] || $object->getId() === null) {
            $data['customFields'] = (object) [];
            return $data;
        }

        $byField = [];
        foreach ($this->values->findForRecord($target, $object->getId()) as $v) {
            $byField[(string) $v->getField()->getId()] = $v;
        }

        $custom = [];
        foreach ($defs as $def) {
            $value = $byField[(string) $def->getId()] ?? null;
            $custom[$def->getFieldKey()] = $this->resolver->deserialize($def, $value?->getValue());
        }

        $data['customFields'] = $custom;

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if ($context[self::SKIP_KEY] ?? false) {
            return false;
        }

        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            return false;
        }

        return $data instanceof Client
            || $data instanceof Contact
            || $data instanceof Invoice
            || $data instanceof RecurringInvoice
            || $data instanceof Quote;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Client::class => false,
            Contact::class => false,
            Invoice::class => false,
            RecurringInvoice::class => false,
            Quote::class => false,
        ];
    }
}
