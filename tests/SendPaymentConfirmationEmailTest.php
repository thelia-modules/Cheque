<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cheque\Tests;

use Cheque\Listener\SendPaymentConfirmationEmail;
use PHPUnit\Framework\TestCase;
use Thelia\Action\Order;
use Thelia\Core\Event\TheliaEvents;

/**
 * The customer must be told once, and only once the payment is recorded, that their
 * cheque order is paid for.
 *
 * Run from a Thelia checkout that has this module installed:
 *   vendor/bin/phpunit --bootstrap vendor/autoload.php vendor/thelia/modules/Cheque/tests
 */
final class SendPaymentConfirmationEmailTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(SendPaymentConfirmationEmail::class)) {
            // A module is not on the Composer autoload map: the kernel registers its
            // classes when it boots, and nothing has booted here.
            spl_autoload_register(static function (string $class): void {
                if (!str_starts_with($class, 'Cheque\\')) {
                    return;
                }

                $file = \dirname(__DIR__).'/'.str_replace('\\', '/', substr($class, \strlen('Cheque\\'))).'.php';

                if (is_file($file)) {
                    require_once $file;
                }
            });
        }
    }

    /**
     * Whether the order is paid for is read from the order itself, and the listener that
     * writes the new status on it is Thelia\Action\Order::updateStatus(). Sharing its
     * priority left the order of the two to the order they happened to be registered in,
     * and on the losing side the customer is never told anything.
     */
    public function testTheEmailIsSentAfterTheCoreHasRecordedTheNewStatus(): void
    {
        self::assertLessThan(
            self::priorityOn(Order::getSubscribedEvents()),
            self::priorityOn(SendPaymentConfirmationEmail::getSubscribedEvents()),
            'The confirmation email must be sent after the core has written the new status.',
        );
    }

    /**
     * The class is an event subscriber and the module lets autoconfiguration register it,
     * so a service declaration for it in Config/config.xml is a second registration: the
     * customer then gets the confirmation email twice.
     */
    public function testTheListenerIsNotDeclaredASecondTimeInTheModuleConfiguration(): void
    {
        $config = simplexml_load_file(\dirname(__DIR__).'/Config/config.xml');
        self::assertNotFalse($config, 'Config/config.xml must be readable.');

        $declaredClasses = [];

        foreach ($config->xpath('//*[local-name()="service"]') ?: [] as $service) {
            $declaredClasses[] = (string) $service['class'];
        }

        self::assertNotContains(SendPaymentConfirmationEmail::class, $declaredClasses);
    }

    /**
     * @param array<string, array{string, int}> $subscribedEvents
     */
    private static function priorityOn(array $subscribedEvents): int
    {
        self::assertArrayHasKey(TheliaEvents::ORDER_UPDATE_STATUS, $subscribedEvents);

        return $subscribedEvents[TheliaEvents::ORDER_UPDATE_STATUS][1];
    }
}
