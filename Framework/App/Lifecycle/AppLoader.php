<?php declare(strict_types=1);

namespace Shopware\Core\Framework\App\Lifecycle;

use Shopware\Core\Framework\App\AppEntity;
use Shopware\Core\Framework\App\Cms\CmsExtensions as CmsManifest;
use Shopware\Core\Framework\App\FlowAction\FlowAction;
use Shopware\Core\Framework\App\Manifest\Manifest;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\CustomEntity\Xml\Config\AdminUi\AdminUiXmlSchema;
use Shopware\Core\System\CustomEntity\Xml\Config\CmsAware\CmsAwareXmlSchema;
use Shopware\Core\System\CustomEntity\Xml\CustomEntityXmlSchema;
use Shopware\Core\System\CustomEntity\Xml\CustomEntityXmlSchemaValidator;
use Shopware\Core\System\SystemConfig\Exception\XmlParsingException;
use Shopware\Core\System\SystemConfig\Util\ConfigReader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 *
 * @package core
 */
class AppLoader extends AbstractAppLoader
{
    private string $appDir;

    private ConfigReader $configReader;

    private string $projectDir;

    private CustomEntityXmlSchemaValidator $customEntityXmlValidator;

    public function __construct(string $appDir, string $projectDir, ConfigReader $configReader, CustomEntityXmlSchemaValidator $customEntityXmlValidator)
    {
        $this->appDir = $appDir;
        $this->configReader = $configReader;
        $this->projectDir = $projectDir;
        $this->customEntityXmlValidator = $customEntityXmlValidator;
    }

    public function getDecorated(): AbstractAppLoader
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @return Manifest[]
     */
    public function load(): array
    {
        if (!file_exists($this->appDir)) {
            return [];
        }

        $finder = new Finder();
        $finder->in($this->appDir)
            ->depth('<= 1') // only use manifest files in app root folders
            ->name('manifest.xml');

        $manifests = [];
        foreach ($finder->files() as $xml) {
            try {
                $manifest = Manifest::createFromXmlFile($xml->getPathname());

                $manifests[$manifest->getMetadata()->getName()] = $manifest;
            } catch (XmlParsingException $e) {
                //nth, if app is already registered it will be deleted
            }
        }

        return $manifests;
    }

    public function getIcon(Manifest $app): ?string
    {
        if (!$app->getMetadata()->getIcon()) {
            return null;
        }

        $iconPath = sprintf('%s/%s', $app->getPath(), $app->getMetadata()->getIcon());
        $icon = @file_get_contents($iconPath);

        if (!$icon) {
            return null;
        }

        return $icon;
    }

    /**
     * @return array<mixed>|null
     */
    public function getConfiguration(AppEntity $app): ?array
    {
        $configPath = sprintf('%s/%s/Resources/config/config.xml', $this->projectDir, $app->getPath());

        if (!file_exists($configPath)) {
            return null;
        }

        return $this->configReader->read($configPath);
    }

    public function deleteApp(string $technicalName): void
    {
        $apps = $this->load();

        if (!isset($apps[$technicalName])) {
            return;
        }

        $manifest = $apps[$technicalName];

        (new Filesystem())->remove($manifest->getPath());
    }

    public function getCmsExtensions(AppEntity $app): ?CmsManifest
    {
        $configPath = sprintf('%s/%s/Resources/cms.xml', $this->projectDir, $app->getPath());

        if (!file_exists($configPath)) {
            return null;
        }

        return CmsManifest::createFromXmlFile($configPath);
    }

    public function getAssetPathForAppPath(string $appPath): string
    {
        return sprintf('%s/%s/Resources/public', $this->projectDir, $appPath);
    }

    public function getEntities(AppEntity $app): ?CustomEntityXmlSchema
    {
        $configPath = sprintf('%s/%s/Resources/entities.xml', $this->projectDir, $app->getPath());

        if (!file_exists($configPath)) {
            return null;
        }

        $entities = CustomEntityXmlSchema::createFromXmlFile($configPath);

        $this->customEntityXmlValidator->validate($entities);

        return $entities;
    }

    public function getFlowActions(AppEntity $app): ?FlowAction
    {
        $configPath = sprintf('%s/%s/Resources/flow-action.xml', $this->projectDir, $app->getPath());

        if (!file_exists($configPath)) {
            return null;
        }

        return FlowAction::createFromXmlFile($configPath);
    }

    public function getFlowActionIcon(?string $iconName, FlowAction $flowAction): ?string
    {
        if (!$iconName) {
            return null;
        }

        $iconPath = sprintf('%s/%s', $flowAction->getPath(), $iconName);
        $icon = @file_get_contents($iconPath);

        if (!$icon) {
            return null;
        }

        return $icon;
    }

    /**
     * @return array<string, string>
     */
    public function getSnippets(AppEntity $app): array
    {
        $snippets = [];

        $path = sprintf('%s/%s/Resources/app/administration/snippet', $this->projectDir, $app->getPath());

        if (!file_exists($path)) {
            return $snippets;
        }

        $finder = new Finder();
        $finder->in($path)
            ->files()
            ->name('*.json');

        foreach ($finder->files() as $file) {
            $snippets[$file->getFilenameWithoutExtension()] = $file->getContents();
        }

        return $snippets;
    }

    public function getCmsAwareXmlSchema(AppEntity $app): ?CmsAwareXmlSchema
    {
        $configPath = sprintf(
            '%s/%s/Resources/config/%s',
            $this->projectDir,
            $app->getPath(),
            CmsAwareXmlSchema::FILENAME
        );

        if (!file_exists($configPath)) {
            return null;
        }

        return CmsAwareXmlSchema::createFromXmlFile($configPath);
    }

    public function getAdminUiXmlSchema(AppEntity $app): ?AdminUiXmlSchema
    {
        $configPath = sprintf(
            '%s/%s/Resources/config/%s',
            $this->projectDir,
            $app->getPath(),
            AdminUiXmlSchema::FILENAME
        );

        if (!file_exists($configPath)) {
            return null;
        }

        return AdminUiXmlSchema::createFromXmlFile($configPath);
    }
}
