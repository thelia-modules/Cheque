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

use Cheque\Cheque;
use Cheque\Hook\Theme\OrderPlacedThemeHook;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Model\ModuleQuery;
use Thelia\Test\IntegrationTestCase;
use TwigEngine\Service\ThemeHookRenderer;

/**
 * The buyer who ordered with a cheque is told, on the confirmation page of a Twig theme, whom to
 * make it payable to and what the shop asks for; nothing is said about an order paid otherwise.
 *
 * Boots the shop the module is installed in, on the test database `bin/test-prepare` creates:
 *   vendor/bin/phpunit --bootstrap vendor/autoload.php vendor/thelia/modules/Cheque/tests
 */
final class OrderPlacedThemeHookTest extends IntegrationTestCase
{
    public function testAnOrderPaidByChequeGetsWhomToPayAndTheInstructions(): void
    {
        Cheque::setConfigValue('payable_to', 'ACME SAS');
        Cheque::setConfigValue('instructions', '<p>Send it to <b>our office</b></p>', 'en_US');

        $html = $this->renderHook($this->createFixtureFactory()->order(overrides: ['paymentModuleCode' => 'Cheque']));

        self::assertStringContainsString('<b>ACME SAS</b>', $html);
        self::assertStringContainsString('<p>Send it to <b>our office</b></p>', $html);
    }

    public function testTheNameIsEscapedInTheSentenceThatKeepsItsMarkup(): void
    {
        Cheque::setConfigValue('payable_to', '<script>alert(1)</script>');

        $html = $this->renderHook($this->createFixtureFactory()->order(overrides: ['paymentModuleCode' => 'Cheque']));

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testAnOrderPaidOtherwiseGetsNothing(): void
    {
        Cheque::setConfigValue('payable_to', 'ACME SAS');

        $otherPaymentModule = ModuleQuery::create()->filterByCode('Cheque', Criteria::NOT_EQUAL)->findOne();
        self::assertNotNull($otherPaymentModule, 'A module other than Cheque is installed.');

        self::assertSame('', trim($this->renderHook($this->createFixtureFactory()->order(overrides: ['paymentModuleCode' => $otherPaymentModule->getCode()]))));
    }

    public function testNoOrderGetsNothing(): void
    {
        self::assertSame('', trim($this->renderer()->render(OrderPlacedThemeHook::HOOK, ['order' => 42])));
    }

    private function renderHook(object $order): string
    {
        return $this->renderer()->render(OrderPlacedThemeHook::HOOK, ['order' => $order]);
    }

    /**
     * Through the renderer of theme_hook(): the hook must be registered, not only written.
     */
    private function renderer(): ThemeHookRenderer
    {
        return static::getContainer()->get(ThemeHookRenderer::class);
    }
}
