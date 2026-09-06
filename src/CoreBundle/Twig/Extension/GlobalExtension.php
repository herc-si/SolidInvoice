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

namespace Augias\CoreBundle\Twig\Extension;

use Augias\CoreBundle\AugiasCoreBundle;
use Augias\CoreBundle\Company\CompanySelector;
use Augias\CoreBundle\Company\ResolvedHost;
use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Listener\HostRoutingListener;
use Augias\CoreBundle\Pdf\Generator;
use Augias\MoneyBundle\Calculator;
use Augias\SettingsBundle\SystemConfig;
use Carbon\Carbon;
use DateTimeInterface;
use Override;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function implode;

/**
 * @see \Augias\CoreBundle\Tests\Twig\Extension\GlobalExtensionTest
 */
class GlobalExtension extends AbstractExtension implements GlobalsInterface
{
    private const string DEFAULT_LOGO = 'png|iVBORw0KGgoAAAANSUhEUgAAADIAAAAfCAYAAAClDZ5ZAAADlklEQVR4nOyYa0gUURTHz5nRdSujFwWpu0VJilBQUhZIBD0Fjd71ofrihyCIiNAkKuxDZFpR1BeFKKgoJSoo2l5QSFAUFvSgF1GWq+sauVkbq7t7T//d2pJa3fXFjuIP5nLnzn397517zpnRaICg0QBhUIjRiGt/c2Fp6nSfP95rMrU5l19618REQv0EDiRVq1OGKM+w58w8KfRAhNxIL2pMF5rtr22bashLBiYo5Hxeei4zXem4mjihrKS19WvFxpuNbjIgwTOisbztvBqPI9YOJ5hH2s/nphWUZ1I8GQwOZSqXpq0X4bXIpmEHLHjNzB01EpGXmnD+mquv7pNB4HCFOOFcmZeaRayvYOL1KBoftrVIuUlvLVp++YOLYgxHqnAtJzWhJU7Lh6CdxJz873MYhUZ0sm3tlVfnKIZEFBIiYNnEk7hPWLZC1H/+x6O02xXeOZWfeMxYxfQNu+rQhR0Kl1d8DY03D/apkYhaSIiq3PQ5SqNTaDglVOZQiXTCP5tcNLSTllJLwtVKpFp0f7X92uE31It0WUiAqtUZJmn170DzXbg1HfdmUy2Npq4hDhiXauzwPSF+ZLeVPqAe0C0hIc4tS5/Y7DWfLlGLsqkXwHl7SCw1sDYPRclLeGAnXsn30bTtkRDr4sIN8ERHkR1FfQgEfofDdiLXgLPnC5Rh4n4IfuFj/WS9reRJt4RYcgoms3A5epv/eyAYNIodImu6OHyxZslxF7FQMWYe/7efGAuBIYl6+KQlhWlxJGcx48ywXcmvFBFBTCRFHjSj2JQywV0Ex7ET1RM6qgYdz8QnK+tulb21LCxMUnFk1RVbFcsEJslAlRnQOI36iA6FjJ23OdFsHrYKg+/G7STqnAMfna7dVFMRMdS3LNk+k0TPJE0girMxganUQxD7Pf0jJGVx4Sx8e8zHyk5G4UQ8ntv+HITtgMSuFG+w3yi9Q90kKa94qOZzzyRFWVg0xHeShcghOdr2ECF+1jODQqw5BXuxOnuoK4icafF4t7juHun1gHHUgh0jhuuUIppKFkVWDTEeHKcVkQEEigeC8X1E9Vj0x17W7zts+5uCQmBOf2AVhkQ3jLgQP+XXXS+9SAYi8M2Os4hXKNKxF/IgPebzqNL6u4c+k8EICBEoeQo9M8JXkVps4dk2JUcRLjjJoAT/oig/rWNdypAdAUFfKBgOUJ1Pia3hRtlj6gfE1B/3JoN/Go3GgBHyEwAA///b8DQCAAAABklEQVQDADVbUjS4R2nPAAAAAElFTkSuQmCC';

    public function __construct(
        private readonly Calculator $calculator,
        private readonly Generator $pdfGenerator,
        private readonly SystemConfig $systemConfig,
        private readonly RequestStack $requestStack,
        private readonly CompanySelector $companySelector,
        private readonly ?string $installed,
        private readonly ToggleInterface $toggler,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException|ServiceCircularReferenceException|ServiceNotFoundException
     */
    public function getGlobals(): array
    {
        return [
            'query' => $this->getQuery(),
            // Hide the version in SaaS mode: hosted deployments often run dev builds,
            // and exposing "3.1.x-dev" in footers/emails looks unprofessional
            'app_version' => $this->toggler->isActive('saas_enabled') ? null : AugiasCoreBundle::VERSION,
            'app_name' => AugiasCoreBundle::APP_NAME,
        ];
    }

    /**
     * @return array<string, string>
     *
     * @throws ServiceCircularReferenceException|ServiceNotFoundException
     */
    protected function getQuery(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (! $request instanceof Request) {
            return [];
        }

        $params = array_merge($request->query->all(), $request->attributes->all());

        foreach (array_keys($params) as $key) {
            if (str_starts_with($key, '_')) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * @return TwigFilter[]
     */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('percentage', $this->calculator->calculatePercentage(...)),

            new TwigFilter('diff', $this->dateDiff(...)),

            new TwigFilter('md5', 'md5'),

            new TwigFilter('repeat', fn (string $string, int $times): string => str_repeat($string, $times)),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('icon', $this->displayIcon(...), ['is_safe' => ['html']]),

            new TwigFunction('app_logo', $this->displayAppLogo(...), ['is_safe' => ['html']]),

            new TwigFunction('company_name', function (): string {
                if ($this->companySelector->getCompany() instanceof Ulid) {
                    return $this->systemConfig->get('system/company/company_name') ?? AugiasCoreBundle::APP_NAME;
                }

                return AugiasCoreBundle::APP_NAME;
            }),

            new TwigFunction('company_id', $this->companySelector->getCompany(...)),

            new TwigFunction('can_print_pdf', $this->pdfGenerator->canPrintPdf(...)),

            new TwigFunction('is_custom_domain', $this->isCustomDomain(...)),
        ];
    }

    public function isCustomDomain(): bool
    {
        $request = $this->requestStack->getMainRequest();
        $resolved = $request?->attributes->get(HostRoutingListener::REQUEST_ATTR);

        return $resolved instanceof ResolvedHost && $resolved->isCustomDomain();
    }

    /**
     * @throws InvalidArgumentException|ServiceCircularReferenceException|ServiceNotFoundException
     */
    public function displayAppLogo(string $width = 'auto', ?Company $company = null, bool $showDefault = false, bool $showOnlyAppIcon = false): string
    {
        $logo = $showDefault ? self::DEFAULT_LOGO : null;

        if ($this->installed && ! $showOnlyAppIcon) {
            $logo = $this->companySelector->getCompany() instanceof Ulid ? $this->systemConfig->get('system/company/logo', $company) : self::DEFAULT_LOGO;

            if (null === $logo) {
                $logo = $showDefault ? self::DEFAULT_LOGO : null;
            }
        }

        if (null === $logo) {
            return '';
        }

        [$type, $logo] = explode('|', $logo);

        return sprintf(
            '<img src="data:image/%s;base64,%s" class="navbar-brand-image m-2" width="%s"/>',
            htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($logo, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $width, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * @param list<string> $options
     */
    public function displayIcon(string $iconName, array $options = []): string
    {
        $class = sprintf('fa fa-%s', $iconName);

        if ([] !== $options) {
            $class .= ' ' . implode(' ', $options);
        }

        return sprintf('<i class="%s"></i>', $class);
    }

    /**
     * Returns a human-readable diff for dates.
     */
    public function dateDiff(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->diffForHumans();
    }
}
