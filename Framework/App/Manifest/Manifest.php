<?php declare(strict_types=1);

namespace Shopware\Core\Framework\App\Manifest;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\App\Manifest\Xml\Admin;
use Shopware\Core\Framework\App\Manifest\Xml\AllowedHosts;
use Shopware\Core\Framework\App\Manifest\Xml\Cookies;
use Shopware\Core\Framework\App\Manifest\Xml\CustomFields;
use Shopware\Core\Framework\App\Manifest\Xml\Metadata;
use Shopware\Core\Framework\App\Manifest\Xml\Payments;
use Shopware\Core\Framework\App\Manifest\Xml\Permissions;
use Shopware\Core\Framework\App\Manifest\Xml\RuleConditions;
use Shopware\Core\Framework\App\Manifest\Xml\Setup;
use Shopware\Core\Framework\App\Manifest\Xml\Storefront;
use Shopware\Core\Framework\App\Manifest\Xml\Webhooks;
use Shopware\Core\Framework\Feature;
use Shopware\Core\System\SystemConfig\Exception\XmlParsingException;
use Symfony\Component\Config\Util\Exception\XmlParsingException as SymfonyXmlParsingException;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * @internal only for use by the app-system, will be considered internal from v6.4.0 onward
 * @package core
 */
#[Package('core')]
class Manifest
{
    private const XSD_FILE = __DIR__ . '/Schema/manifest-2.0.xsd';

    private const DEPRECATED_XSD_FILE = __DIR__ . '/Schema/manifest-1.0.xsd';

    private string $path;

    private Metadata $metadata;

    private ?Setup $setup;

    private ?Admin $admin;

    private ?Permissions $permissions;

    private ?AllowedHosts $allowedHosts;

    private ?CustomFields $customFields;

    private ?Webhooks $webhooks;

    private ?Cookies $cookies;

    private ?Payments $payments;

    private ?RuleConditions $ruleConditions;

    private ?Storefront $storefront;

    private function __construct(
        string $path,
        Metadata $metadata,
        ?Setup $setup,
        ?Admin $admin,
        ?Permissions $permissions,
        ?AllowedHosts $allowedHosts,
        ?CustomFields $customFields,
        ?Webhooks $webhooks,
        ?Cookies $cookies,
        ?Payments $payments,
        ?RuleConditions $ruleConditions,
        ?Storefront $storefront
    ) {
        $this->path = $path;
        $this->metadata = $metadata;
        $this->setup = $setup;
        $this->admin = $admin;
        $this->permissions = $permissions;
        $this->allowedHosts = $allowedHosts;
        $this->customFields = $customFields;
        $this->webhooks = $webhooks;
        $this->cookies = $cookies;
        $this->payments = $payments;
        $this->ruleConditions = $ruleConditions;
        $this->storefront = $storefront;
    }

    /**
     * @deprecated tag:v6.5.0 - will not validate against manifest-1.0.xsd anymore
     */
    public static function createFromXmlFile(string $xmlFile): self
    {
        try {
            try {
                $doc = XmlUtils::loadFile($xmlFile, self::XSD_FILE);
            } catch (SymfonyXmlParsingException $parsingException) {
                Feature::triggerDeprecationOrThrow(
                    'v6.5.0.0',
                    sprintf(
                        'Trying to parse manifest file with deprecated schema file: "%s". Reason: %s',
                        self::DEPRECATED_XSD_FILE,
                        $parsingException->getMessage(),
                    )
                );

                $doc = XmlUtils::loadFile($xmlFile, self::DEPRECATED_XSD_FILE);
            }

            /** @var \DOMElement $meta */
            $meta = $doc->getElementsByTagName('meta')->item(0);
            $metadata = Metadata::fromXml($meta);
            $setup = $doc->getElementsByTagName('setup')->item(0);
            $setup = $setup === null ? null : Setup::fromXml($setup);
            $admin = $doc->getElementsByTagName('admin')->item(0);
            $admin = $admin === null ? null : Admin::fromXml($admin);
            $permissions = $doc->getElementsByTagName('permissions')->item(0);
            $permissions = $permissions === null ? null : Permissions::fromXml($permissions);
            $allowedHosts = $doc->getElementsByTagName('allowed-hosts')->item(0);
            $allowedHosts = $allowedHosts === null ? null : AllowedHosts::fromXml($allowedHosts);
            $customFields = $doc->getElementsByTagName('custom-fields')->item(0);
            $customFields = $customFields === null ? null : CustomFields::fromXml($customFields);
            $webhooks = $doc->getElementsByTagName('webhooks')->item(0);
            $webhooks = $webhooks === null ? null : Webhooks::fromXml($webhooks);
            $cookies = $doc->getElementsByTagName('cookies')->item(0);
            $cookies = $cookies === null ? null : Cookies::fromXml($cookies);
            $payments = $doc->getElementsByTagName('payments')->item(0);
            $payments = $payments === null ? null : Payments::fromXml($payments);
            $ruleConditions = $doc->getElementsByTagName('rule-conditions')->item(0);
            $ruleConditions = $ruleConditions === null ? null : RuleConditions::fromXml($ruleConditions);
            $storefront = $doc->getElementsByTagName('storefront')->item(0);
            $storefront = $storefront === null ? null : Storefront::fromXml($storefront);
        } catch (\Exception $e) {
            throw new XmlParsingException($xmlFile, $e->getMessage());
        }

        return new self(\dirname($xmlFile), $metadata, $setup, $admin, $permissions, $allowedHosts, $customFields, $webhooks, $cookies, $payments, $ruleConditions, $storefront);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getSetup(): ?Setup
    {
        return $this->setup;
    }

    public function getAdmin(): ?Admin
    {
        return $this->admin;
    }

    public function getPermissions(): ?Permissions
    {
        return $this->permissions;
    }

    public function getAllowedHosts(): ?AllowedHosts
    {
        return $this->allowedHosts;
    }

    /**
     * @param array<string, string[]> $permission
     */
    public function addPermissions(array $permission): void
    {
        if ($this->permissions === null) {
            $this->permissions = Permissions::fromArray([]);
        }

        $this->permissions->add($permission);
    }

    public function getCustomFields(): ?CustomFields
    {
        return $this->customFields;
    }

    public function getWebhooks(): ?Webhooks
    {
        return $this->webhooks;
    }

    public function getCookies(): ?Cookies
    {
        return $this->cookies;
    }

    public function getPayments(): ?Payments
    {
        return $this->payments;
    }

    public function getRuleConditions(): ?RuleConditions
    {
        return $this->ruleConditions;
    }

    public function getStorefront(): ?Storefront
    {
        return $this->storefront;
    }

    /**
     * @return array<string> all hosts referenced in the manifest file
     */
    public function getAllHosts(): array
    {
        $hosts = $this->allowedHosts ? $this->allowedHosts->getHosts() : [];

        $urls = [];
        if ($this->setup) {
            $urls[] = $this->setup->getRegistrationUrl();
        }

        if ($this->webhooks) {
            $urls = array_merge($urls, $this->webhooks->getUrls());
        }

        if ($this->admin) {
            $urls = array_merge($urls, $this->admin->getUrls());
        }

        if ($this->payments) {
            $urls = array_merge($urls, $this->payments->getUrls());
        }

        $urls = array_map(fn (string $url) => \parse_url($url, \PHP_URL_HOST), $urls);

        return array_values(array_unique(array_merge($hosts, $urls)));
    }
}
