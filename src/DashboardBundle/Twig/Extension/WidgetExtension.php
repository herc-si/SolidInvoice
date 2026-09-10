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

namespace Augias\DashboardBundle\Twig\Extension;

use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Layout\LayoutProviderInterface;
use Augias\DashboardBundle\Widgets\WidgetDefinition;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Renders the dashboard from the current user's resolved layout.
 *
 * @see \Augias\DashboardBundle\Tests\Twig\Extension\WidgetExtensionTest
 */
final class WidgetExtension extends AbstractExtension
{
    private const string WRAPPER_TEMPLATE = '@AugiasDashboard/Widget/_wrapper.html.twig';

    private const string ERROR_TEMPLATE = '@AugiasDashboard/Widget/_widget_error.html.twig';

    public function __construct(
        private readonly LayoutProviderInterface $layoutProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'render_dashboard_zone',
                fn (Environment $environment, string $zone): string => $this->renderDashboardZone($environment, $zone),
                ['needs_environment' => true, 'is_safe' => ['html']],
            ),
            new TwigFunction(
                'dashboard_hidden_widgets',
                fn (): array => $this->hiddenWidgets(),
            ),
            new TwigFunction(
                'dashboard_widget_widths',
                static fn (): array => WidgetWidth::vocabulary(),
            ),
        ];
    }

    public function renderDashboardZone(Environment $environment, string $zone): string
    {
        $resolved = WidgetZone::tryFrom($zone);

        if (! $resolved instanceof WidgetZone) {
            $this->logger->warning('Unknown dashboard zone requested', ['zone' => $zone]);

            return '';
        }

        $content = '';

        foreach ($this->layoutProvider->currentLayout()->zone($resolved) as $definition) {
            $content .= $this->renderWidget($environment, $definition, $resolved);
        }

        return $content;
    }

    /**
     * Widgets the user has put away, for the "add a widget" picker.
     *
     * @return list<WidgetDefinition>
     */
    public function hiddenWidgets(): array
    {
        return $this->layoutProvider->currentLayout()->hidden;
    }

    /**
     * One widget, in its wrapper, with its failure contained.
     *
     * The old renderer had no try/catch, so a single failing query took the
     * whole page down — and only three of seven widgets caught their own. A
     * broken widget is now a broken card: the rest of the dashboard still
     * answers the questions it can.
     */
    private function renderWidget(Environment $environment, WidgetDefinition $definition, WidgetZone $zone): string
    {
        try {
            $content = $environment->render($definition->widget->getTemplate(), $definition->widget->getData());
        } catch (Throwable $e) {
            $this->logger->error('Unable to render a dashboard widget', [
                'widget' => $definition->id,
                'exception' => $e,
            ]);

            $content = $environment->render(self::ERROR_TEMPLATE);
        }

        // A widget that deliberately renders nothing gets no wrapper: an empty
        // card would otherwise show up as a gap with a drag handle floating in it.
        if ('' === trim($content)) {
            return '';
        }

        return $environment->render(self::WRAPPER_TEMPLATE, [
            'widget' => $definition,
            'zone' => $zone->value,
            'content' => $content,
        ]);
    }
}
