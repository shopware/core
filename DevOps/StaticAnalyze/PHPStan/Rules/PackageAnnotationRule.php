<?php declare(strict_types=1);

namespace Shopware\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;

/**
 * @implements Rule<InClassNode>
 *
 * @internal
 *
 * @package core
 */
class PackageAnnotationRule implements Rule
{
    /**
     * @internal
     */
    public const PRODUCT_AREA_MAPPING = [
        'core' => [
            '/Shopware\\\\Core\\\\Framework\\\\(Adapter|Api|App|Changelog|DataAbstractionLayer|Demodata|DependencyInjection|)\\\\/',
            '/Shopware\\\\Core\\\\Framework\\\\(Increment|Log|MessageQueue|Migration|Parameter|Plugin|RateLimiter|Script|Routing|Struct|Util|Uuid|Validation|Webhook)\\\\/',
            '/Shopware\\\\Core\\\\DevOps\\\\/',
            '/Shopware\\\\Core\\\\Installer\\\\/',
            '/Shopware\\\\Core\\\\Maintenance\\\\/',
            '/Shopware\\\\Core\\\\Migration\\\\/',
            '/Shopware\\\\Core\\\\Profiling\\\\/',
            '/Shopware\\\\Elasticsearch\\\\/',
            '/Shopware\\\\Core\\\\System\\\\(Annotation|CustomEntity|DependencyInjection|SystemConfig)\\\\/',
            '/Shopware\\\\.*\\\\(DataAbstractionLayer)\\\\/',
        ],
        'business-ops' => [
            '/Shopware\\\\.*\\\\(Rule|Flow|ProductStream)\\\\/',
            '/Shopware\\\\Core\\\\Framework\\\\(Event)\\\\/',
            '/Shopware\\\\Core\\\\System\\\\(Tag)\\\\/',
        ],
        'inventory' => [
            '/Shopware\\\\Core\\\\Content\\\\(Product|ProductExport|Property)\\\\/',
            '/Shopware\\\\Core\\\\System\\\\(Currency|Unit)\\\\/',
        ],
        'content' => [
            '/Shopware\\\\Core\\\\Content\\\\(Media|Category|Cms|ContactForm|LandingPage)\\\\/',
        ],
        'system-settings' => [
            '/Shopware\\\\Core\\\\Content\\\\(ImportExport|Mail|)\\\\/',
            '/Shopware\\\\Core\\\\Framework\\\\(Update)\\\\/',
            '/Shopware\\\\Core\\\\System\\\\(Country|CustomField|Integration|Language|Locale|Snippet|User)\\\\/',
        ],
        'sales-channel' => [
            '/Shopware\\\\Core\\\\Content\\\\(MailTemplate|Seo|Sitemap)\\\\/',
            '/Shopware\\\\Core\\\\System\\\\(SalesChannel)\\\\/',
        ],
        'customer-order' => [
            '/Shopware\\\\Core\\\\Content\\\\(Newsletter)\\\\/',
            '/Shopware\\\\Core\\\\Checkout\\\\(Customer|Document|Order)\\\\/',
            '/Shopware\\\\Core\\\\System\\\\(DeliveryTime|Salutation|Tax)\\\\/',
        ],
        'checkout' => [
            '/Shopware\\\\Core\\\\Checkout\\\\(Cart|Payment|Promotion|Shipping)\\\\/',
            '/Shopware\\\\Core\\\\System\\\\(DeliveryTime|NumberRange|StateMachine)\\\\/',
            '/Shopware\\\\Storefront\\\\(Checkout)\\\\/',
        ],
        'merchant-services' => [
            '/Shopware\\\\Core\\\\Framework\\\\(Store)\\\\/',
        ],
        'storefront' => [
            '/Shopware\\\\Storefront\\\\(DependencyInjection)\\\\/',
        ],
    ];

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return array<array-key, RuleError|string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isTestClass($node)) {
            return [];
        }

        $area = $this->getProductArea($node);

        if ($this->hasPackageAnnotation($node)) {
            return [];
        }

        return [sprintf('This class is missing the "@package" annotation (recommendation: %s)', $area ?? 'unknown')];
    }

    private function getProductArea(InClassNode $node): ?string
    {
        $namespace = $node->getClassReflection()->getName();

        foreach (self::PRODUCT_AREA_MAPPING as $area => $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match($regex, $namespace)) {
                    return $area;
                }
            }
        }

        return null;
    }

    private function hasPackageAnnotation(InClassNode $class): bool
    {
        $doc = $class->getDocComment();

        if ($doc === null) {
            return false;
        }

        return \str_contains($doc->getText(), sprintf('@package'));
    }

    private function isTestClass(InClassNode $node): bool
    {
        $namespace = $node->getClassReflection()->getName();

        return \str_contains($namespace, 'Shopware\\Tests\\') || \str_contains($namespace, '\\Test\\');
    }
}
