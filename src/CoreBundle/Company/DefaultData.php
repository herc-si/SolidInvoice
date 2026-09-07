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

namespace Augias\CoreBundle\Company;

use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Entity\CustomField\CustomField;
use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Enum\CustomFieldType;
use Augias\PaymentBundle\Entity\PaymentMethod;
use Augias\SettingsBundle\Config\ProviderInterface;
use Augias\SettingsBundle\DTO\Config;
use Augias\SettingsBundle\Entity\Setting;
use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use JsonException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Contracts\Translation\TranslatorInterface;
use function get_debug_type;

/**
 * @see \Augias\CoreBundle\Tests\Company\DefaultDataTest
 */
final readonly class DefaultData
{
    private ObjectManager $em;

    /**
     * @param iterable<ProviderInterface> $configProviders
     */
    public function __construct(
        ManagerRegistry $registry,
        #[AutowireIterator(ProviderInterface::class)]
        private iterable $configProviders,
        private TranslatorInterface $translator,
    ) {
        $this->em = $registry->getManager();
    }

    /**
     * @param array{currency: string, locale?: string|null} $data
     * @throws JsonException
     */
    public function __invoke(Company $company, array $data): void
    {
        // The company's own language, not the request's: a company is created
        // during install and from the company switcher, and in neither case is
        // the acting user's locale necessarily the one this company will be
        // run in.
        //
        // Normalised to null when blank, because the caller reads it off the
        // request and Request::getLocale() can hand back an empty string. A
        // `?? 'en'` further down does not catch that — it is not null — so an
        // empty locale used to be stored verbatim as the company's language
        // setting, leaving it blank rather than defaulting to English.
        $locale = $data['locale'] ?? null;

        if ('' === $locale) {
            $locale = null;
        }

        $data['locale'] = $locale;

        $this->createAppConfig($company, $data);
        $this->createDefaultCustomFields($company, $locale);
        $this->createPaymentMethods($locale);

        $this->em->flush();
    }

    private function createDefaultCustomFields(Company $company, ?string $locale): void
    {
        $defaults = [
            ['additional_email', 'custom_field.default.additional_email', CustomFieldType::EMAIL, 0],
            ['phone', 'custom_field.default.phone', CustomFieldType::TEXT, 1],
            ['mobile', 'custom_field.default.mobile', CustomFieldType::TEXT, 2],
        ];

        foreach ($defaults as [$key, $labelKey, $type, $position]) {
            $field = new CustomField()
                ->setTarget(CustomFieldTarget::CONTACT)
                ->setLabel($this->translate($labelKey, $locale))
                ->setFieldKey($key)
                ->setType($type)
                ->setPosition($position)
                ->setCompany($company);
            $this->em->persist($field);
        }
    }

    /**
     * Seeded labels are ordinary user-editable data, not translation keys — the
     * user can rename them, and nothing resolves them again afterwards. So they
     * are translated once, here, into the language the company was created in,
     * and stored as plain text. Translating them on display instead would
     * silently overwrite whatever the user renamed them to.
     */
    private function translate(string $key, ?string $locale): string
    {
        return $this->translator->trans($key, [], null, $locale);
    }

    /**
     * @param array{currency: string, locale?: string|null} $data
     * @throws JsonException
     */
    private function createAppConfig(Company $company, array $data): void
    {
        foreach ($this->configProviders as $provider) {
            foreach ($provider->provide($data + ['company_name' => $company->getName()]) as $config) {
                if (! $config instanceof Config) {
                    throw new RuntimeException(sprintf('Config provider %s did not return an instance of %s. %s returned.', $provider::class, Config::class, get_debug_type($config)));
                }

                $settingEntity = new Setting();
                $settingEntity->setKey($config->key);
                $settingEntity->setValue($config->value);
                $settingEntity->setDescription($config->description);
                $settingEntity->setType($config->formType);
                $settingEntity->setFormOptions($config->formOptions);
                $settingEntity->setDefaultValue($config->value);
                $settingEntity->setCompany($company);

                $this->em->persist($settingEntity);
            }
        }
    }

    private function createPaymentMethods(?string $locale): void
    {
        $paymentMethods = [
            [
                'name' => 'payment.method.default.cash',
                'gateway_name' => 'cash',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
            [
                'name' => 'payment.method.default.bank_transfer',
                'gateway_name' => 'bank_transfer',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
            [
                'name' => 'payment.method.default.credit',
                'gateway_name' => 'credit',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethodEntity = new PaymentMethod();
            $paymentMethodEntity->setName($this->translate($paymentMethod['name'], $locale));
            $paymentMethodEntity->setGatewayName($paymentMethod['gateway_name']);
            $paymentMethodEntity->setConfig($paymentMethod['config']);
            $paymentMethodEntity->setInternal($paymentMethod['internal']);
            $paymentMethodEntity->setEnabled($paymentMethod['enabled']);
            $paymentMethodEntity->setFactoryName($paymentMethod['factory']);
            $paymentMethodEntity->setCreated(Carbon::now());
            $paymentMethodEntity->setUpdated(Carbon::now());

            $this->em->persist($paymentMethodEntity);
        }
    }
}
