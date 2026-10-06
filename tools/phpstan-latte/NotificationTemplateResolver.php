<?php

declare(strict_types=1);

namespace openvk\PHPStanLatte;

use Efabrica\PHPStanLatte\Analyser\AnalysedTemplatesRegistry;
use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteTemplateResolver\CustomLatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\Type\ObjectType;
use function str_contains;

/**
 * Resolves notification templates (components/notifications/{type}/*.latte).
 *
 * NotificationPresenter renders them dynamically depending on the notification
 * type, so static analysis cannot follow a render call. All of them share the
 * same contract: they receive the "notification" variable.
 */
final class NotificationTemplateResolver implements CustomLatteTemplateResolverInterface
{
    private const NOTIFICATIONS_PATH = '/components/notifications/';

    private AnalysedTemplatesRegistry $analysedTemplatesRegistry;

    public function __construct(AnalysedTemplatesRegistry $analysedTemplatesRegistry)
    {
        $this->analysedTemplatesRegistry = $analysedTemplatesRegistry;
    }

    public function collect(): array
    {
        return [new CollectedResolvedNode(static::class, __FILE__, [])];
    }

    public function resolve(CollectedResolvedNode $resolvedNode, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();

        $templateContext = new TemplateContext([
            new Variable('notification', new ObjectType('\openvk\Web\Models\Entities\Notifications\Notification')),
        ]);

        foreach ($this->analysedTemplatesRegistry->getExistingTemplates() as $templatePath) {
            if (!str_contains($templatePath, self::NOTIFICATIONS_PATH)) {
                continue;
            }

            $result->addTemplate(new Template($templatePath, null, null, $templateContext));
        }

        return $result;
    }
}
