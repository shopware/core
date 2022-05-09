<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Document\DocumentGenerator;

use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\DocumentConfigurationFactory;
use Shopware\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Feature;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\Country\Service\CountryAddressFormattingService;
use Shopware\Core\System\Country\Struct\CountryAddress;
use Twig\Error\Error;

/**
 * @deprecated tag:v6.5.0 - Will be removed, use StornoRenderer instead
 */
class StornoGenerator implements DocumentGeneratorInterface
{
    public const DEFAULT_TEMPLATE = '@Framework/documents/storno.html.twig';
    public const STORNO = 'storno';

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var DocumentTemplateRenderer
     */
    private $documentTemplateRenderer;

    private CountryAddressFormattingService $countryAddressFormattingService;

    /**
     * @internal
     */
    public function __construct(
        DocumentTemplateRenderer $documentTemplateRenderer,
        string $rootDir,
        CountryAddressFormattingService $countryAddressFormattingService
    ) {
        $this->rootDir = $rootDir;
        $this->documentTemplateRenderer = $documentTemplateRenderer;
        $this->countryAddressFormattingService = $countryAddressFormattingService;
    }

    public function supports(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.5.0.0',
            Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.5.0.0')
        );

        return self::STORNO;
    }

    /**
     * @throws Error
     */
    public function generate(
        OrderEntity $order,
        DocumentConfiguration $config,
        Context $context,
        ?string $templatePath = null
    ): string {
        Feature::triggerDeprecationOrThrow(
            'v6.5.0.0',
            Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.5.0.0', 'StornoRenderer::render')
        );

        $templatePath = $templatePath ?? self::DEFAULT_TEMPLATE;

        $order = $this->handlePrices($order);

        /** @var LanguageEntity $language */
        $language = $order->getLanguage();
        /** @var LocaleEntity $locale */
        $locale = $language->getLocale();

        $parameters = [
            'order' => $order,
            'config' => DocumentConfigurationFactory::mergeConfiguration($config, new DocumentConfiguration())->jsonSerialize(),
            'rootDir' => $this->rootDir,
            'context' => $context,
        ];

        if ($formattingAddress = $this->renderFormattingAddress($order, $context)) {
            $parameters['formattingAddress'] = $formattingAddress;
        }

        $documentString = $this->documentTemplateRenderer->render(
            $templatePath,
            $parameters            $context,
            $order->getSalesChannelId(),
            $order->getLanguageId(),
            $locale->getCode()
        );

        return $documentString;
    }

    public function getFileName(DocumentConfiguration $config): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.5.0.0',
            Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.5.0.0')
        );

        return $config->getFilenamePrefix() . $config->getDocumentNumber() . $config->getFilenameSuffix();
    }

    private function handlePrices(OrderEntity $order): OrderEntity
    {
        foreach ($order->getLineItems() ?? [] as $lineItem) {
            $lineItem->setUnitPrice($lineItem->getUnitPrice() / -1);
            $lineItem->setTotalPrice($lineItem->getTotalPrice() / -1);
        }
        foreach ($order->getPrice()->getCalculatedTaxes()->sortByTax()->getElements() as $tax) {
            $tax->setTax($tax->getTax() / -1);
        }

        $order->setShippingTotal($order->getShippingTotal() / -1);
        $order->setAmountNet($order->getAmountNet() / -1);
        $order->setAmountTotal($order->getAmountTotal() / -1);
        $order->getPrice()->assign([
            'rawTotal' => $order->getPrice()->getRawTotal() / -1,
            'totalPrice' => $order->getPrice()->getTotalPrice() / -1,
        ]);

        return $order;
    }

    private function renderFormattingAddress(OrderEntity $order, Context $context): ?string
    {
        if (!$order->getAddresses()) {
            return null;
        }

        $billingAddress = $order->getAddresses()->get($order->getBillingAddressId());
        if ($billingAddress && $billingAddress->getCountry() && !$billingAddress->getCountry()->getUseDefaultAddressFormat()) {
            return $this->countryAddressFormattingService->render(
                CountryAddress::createFromEntity($billingAddress),
                $billingAddress->getCountry()->getAdvancedAddressFormatPlain(),
                $context,
            );
        }

        return null;
    }
}
