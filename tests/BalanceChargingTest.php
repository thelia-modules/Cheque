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
use PHPUnit\Framework\TestCase;
use Thelia\Module\BalanceChargingPaymentModuleInterface;

/**
 * The module pays no provider and shows no amount, so it can carry the balance left by an
 * instant means such as a gift card: the core refuses that combination unless it is declared.
 * No database, no kernel.
 */
final class BalanceChargingTest extends TestCase
{
    public function testTheModuleDeclaresItCanChargeTheBalance(): void
    {
        self::assertTrue(is_subclass_of(Cheque::class, BalanceChargingPaymentModuleInterface::class));
    }
}
