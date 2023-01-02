<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Api\Serializer;

use Shopware\Core\Framework\Log\Package;
/**
 * @package core
 */
#[Package('core')]
class JsonApiEncodingResult implements \JsonSerializable
{
    /**
     * @var Record[]
     */
    protected $data = [];

    /**
     * @var Record[]
     */
    protected $included = [];

    /**
     * @var array
     */
    protected $keyCollection = [];

    /**
     * @var bool
     */
    protected $single = false;

    /**
     * @var array
     */
    protected $metaData = [];

    /**
     * @var string
     */
    protected $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getIncluded(): array
    {
        return $this->included;
    }

    public function addEntity(Record $entity): void
    {
        $key = $entity->getId() . '-' . $entity->getType();

        $this->data[$key] = $entity;

        if (isset($this->included[$key])) {
            unset($this->included[$key]);
        }

        $this->keyCollection[$key] = 1;
    }

    public function addIncluded(Record $entity): void
    {
        $key = $entity->getId() . '-' . $entity->getType();

        if ($this->contains($entity->getId(), $entity->getType())) {
            $this->mergeRecords($this->included[$key], $entity);

            return;
        }

        $this->included[$key] = $entity;

        $this->keyCollection[$key] = 1;
    }

    public function contains(string $id, string $type): bool
    {
        $key = $id . '-' . $type;

        return isset($this->keyCollection[$key]);
    }

    public function containsInIncluded(string $id, string $type): bool
    {
        $key = $id . '-' . $type;

        return isset($this->included[$key]);
    }

    public function containsInData(string $id, string $type): bool
    {
        $key = $id . '-' . $type;

        return isset($this->data[$key]);
    }

    /**
     * @deprecated tag:v6.5.0 - reason:return-type-change - return type will be changed to string
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $output = [
            'data' => $this->isSingle() ? array_shift($this->data) : array_values($this->data),
            'included' => array_values($this->included),
        ];

        if (!empty($this->metaData)) {
            $output = array_merge($output, $this->metaData);
        }

        return $output;
    }

    public function isSingle(): bool
    {
        return $this->single;
    }

    public function setSingleResult(bool $single): void
    {
        $this->single = $single;
    }

    public function setMetaData(array $metaData): void
    {
        $this->metaData = $metaData;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    protected function mergeRecords(Record $recordA, Record $recordB): void
    {
        foreach ($recordB->getAttributes() as $key => $value) {
            if (!empty($value)) {
                $recordA->setAttribute($key, $value);
            }
        }

        foreach ($recordB->getRelationships() as $key => $value) {
            if ($value['data'] === null) {
                continue;
            }
            $recordA->addRelationship($key, $value);
        }

        foreach ($recordB->getExtensions() as $key => $value) {
            if ($value['data'] === null) {
                continue;
            }
            $recordA->addExtension($key, $value);
        }

        foreach ($recordB->getLinks() as $key => $value) {
            if (!empty($value)) {
                $recordA->addLink($key, $value);
            }
        }
    }
}
