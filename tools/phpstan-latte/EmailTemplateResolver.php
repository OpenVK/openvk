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
use PHPStan\Type\StringType;

use function str_contains;
use function str_ends_with;

/**
 * Resolves mail templates (Email/*.eml.latte).
 *
 * Presenters send them through SimplePresenter::sendmail($to, $template,
 * $params), which builds the path dynamically, so no render call can be
 * followed. All mail templates share the same contract: "key" and "name"
 * are strings supplied in $params.
 */
final class EmailTemplateResolver implements CustomLatteTemplateResolverInterface
{
    private const EMAIL_PATH = '/Email/';

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
            new Variable('key', new StringType()),
            new Variable('name', new StringType()),
        ]);

        foreach ($this->analysedTemplatesRegistry->getExistingTemplates() as $templatePath) {
            if (!str_contains($templatePath, self::EMAIL_PATH) || !str_ends_with($templatePath, '.eml.latte')) {
                continue;
            }

            $result->addTemplate(new Template($templatePath, null, null, $templateContext));
        }

        return $result;
    }
}
