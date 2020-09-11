<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\Api\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Shopware\Core\Kernel;
use Shopware\Core\PlatformRequest;

class InfoControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    public function testGetConfig(): void
    {
        $expected = [
            'version' => Kernel::SHOPWARE_FALLBACK_VERSION,
            'versionRevision' => str_repeat('0', 32),
            'adminWorker' => [
                'enableAdminWorker' => $this->getContainer()->getParameter('shopware.admin_worker.enable_admin_worker'),
                'transports' => $this->getContainer()->getParameter('shopware.admin_worker.transports'),
            ],
            'bundles' => [],
            'settings' => [
                'enableUrlFeature' => true,
            ],
        ];

        $url = sprintf('/api/v%s/_info/config', PlatformRequest::API_VERSION);
        $client = $this->getBrowser();
        $client->request('GET', $url);

        static::assertJson($client->getResponse()->getContent());

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);

        static::assertSame(200, $client->getResponse()->getStatusCode());
        static::assertSame(array_keys($expected), array_keys($decodedResponse));

        unset($expected['settings']);
        static::assertStringStartsWith(mb_substr(json_encode($expected), 0, -3), $client->getResponse()->getContent());
    }

    public function testBusinessEventRoute(): void
    {
        Feature::skipTestIfInActive('FEATURE_NEXT_9351', $this);

        $url = sprintf('/api/v%s/_info/business-events.json', PlatformRequest::API_VERSION);
        $client = $this->getBrowser();
        $client->request('GET', $url);

        static::assertJson($client->getResponse()->getContent());

        $response = json_decode($client->getResponse()->getContent(), true);

        static::assertSame(200, $client->getResponse()->getStatusCode());

        $expected = [
            [
                'name' => 'checkout.customer.login',
                'class' => "Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent",
                'mailAware' => false,
                'logAware' => false,
                'extensions' => [],
            ],
            [
                'name' => 'checkout.order.placed',
                'class' => "Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent",
                'mailAware' => true,
                'logAware' => false,
                'extensions' => [],
            ],
            [
                'name' => 'state_enter.order_delivery.state.shipped_partially',
                'class' => "Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent",
                'mailAware' => true,
                'logAware' => false,
                'extensions' => [],
            ],
            [
                'name' => 'state_enter.order_transaction.state.cash_payment.cancelled',
                'class' => "Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent",
                'mailAware' => true,
                'logAware' => false,
                'extensions' => [],
            ],
        ];

        foreach ($expected as $event) {
            static::assertContains($event, $response);
        }
    }
}
