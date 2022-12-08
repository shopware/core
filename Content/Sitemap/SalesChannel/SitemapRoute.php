<?php declare(strict_types=1);

namespace Shopware\Core\Content\Sitemap\SalesChannel;

use Shopware\Core\Content\Sitemap\Exception\AlreadyLockedException;
use Shopware\Core\Content\Sitemap\Service\SitemapExporterInterface;
use Shopware\Core\Content\Sitemap\Service\SitemapListerInterface;
use Shopware\Core\Content\Sitemap\Struct\SitemapCollection;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package sales-channel
 *
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class SitemapRoute extends AbstractSitemapRoute
{
    /**
     * @var SitemapListerInterface
     */
    private $sitemapLister;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var SitemapExporterInterface
     */
    private $sitemapExporter;

    /**
     * @internal
     */
    public function __construct(SitemapListerInterface $sitemapLister, SystemConfigService $systemConfigService, SitemapExporterInterface $sitemapExporter)
    {
        $this->sitemapLister = $sitemapLister;
        $this->systemConfigService = $systemConfigService;
        $this->sitemapExporter = $sitemapExporter;
    }

    /**
     * @Since("6.3.2.0")
     * @Route(path="/store-api/sitemap", name="store-api.sitemap", methods={"GET", "POST"})
     */
    public function load(Request $request, SalesChannelContext $context): SitemapRouteResponse
    {
        $sitemaps = $this->sitemapLister->getSitemaps($context);

        if ($this->systemConfigService->getInt('core.sitemap.sitemapRefreshStrategy') !== SitemapExporterInterface::STRATEGY_LIVE) {
            return new SitemapRouteResponse(new SitemapCollection($sitemaps));
        }

        // Close session to prevent session locking from waiting in case there is another request coming in
        if ($request->hasSession() && session_status() === \PHP_SESSION_ACTIVE) {
            $request->getSession()->save();
        }

        try {
            $this->generateSitemap($context, true);
        } catch (AlreadyLockedException $exception) {
            // Silent catch, lock couldn't be acquired. Some other process already generates the sitemap.
        }

        $sitemaps = $this->sitemapLister->getSitemaps($context);

        return new SitemapRouteResponse(new SitemapCollection($sitemaps));
    }

    public function getDecorated(): AbstractSitemapRoute
    {
        throw new DecorationPatternException(self::class);
    }

    private function generateSitemap(SalesChannelContext $salesChannelContext, bool $force, ?string $lastProvider = null, ?int $offset = null): void
    {
        $result = $this->sitemapExporter->generate($salesChannelContext, $force, $lastProvider, $offset);
        if ($result->isFinish() === false) {
            $this->generateSitemap($salesChannelContext, $force, $result->getProvider(), $result->getOffset());
        }
    }
}
