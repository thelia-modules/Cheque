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

namespace Cheque\Hook\Theme;

use Cheque\Cheque;
use Symfony\Contracts\Translation\TranslatorInterface;
use Thelia\Core\Hook\Theme\ThemeHookInterface;
use Thelia\Domain\Localization\Service\LangService;
use Thelia\Model\ConfigQuery;
use Thelia\Model\CountryQuery;
use Thelia\Model\Lang;
use Thelia\Model\Order;
use Twig\Environment;

/**
 * Tells the buyer who just ordered with a cheque whom to make it payable to and where to send
 * it, on the confirmation page of a Twig front-office theme.
 *
 * A theme hook, not the legacy order-placed.additional-payment-info hook of HookManager: that
 * one is rendered by the Smarty {hook} tag, and a Thelia 3 theme declares its extension points
 * with theme_hook() instead. The theme passes the order it shows, and the module only answers
 * for an order paid by cheque: every payment module is asked at the same point.
 *
 * A theme words the block its own way by putting modules/Cheque/<template> in its folder, as it
 * did with the Smarty template; the module's own template is used otherwise.
 */
final readonly class OrderPlacedThemeHook implements ThemeHookInterface
{
    public const HOOK = 'order-placed.additional-payment-info';

    public const TEMPLATE = 'order-placed.additional-payment-info.html.twig';

    private const THEME_TEMPLATE = 'modules/Cheque/'.self::TEMPLATE;

    private const MODULE_TEMPLATE = '@ChequeModule/frontOffice/default/'.self::TEMPLATE;

    private const DOMAIN = 'cheque.fo.default';

    public function __construct(
        private Environment $twig,
        private TranslatorInterface $translator,
        private LangService $langService,
    ) {
    }

    public function supports(string $hookName): bool
    {
        return self::HOOK === $hookName;
    }

    public function render(string $hookName, array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        if (!$order instanceof Order || (int) $order->getPaymentModuleId() !== (int) Cheque::getModuleId()) {
            return '';
        }

        $locale = $this->locale();
        $payableTo = (string) Cheque::getConfigValue('payable_to', '');

        return $this->twig->render($this->template(), [
            'order' => $order,
            'payable_to' => $payableTo,
            // The name is escaped before it goes into a sentence that keeps its own markup.
            'payable_to_sentence' => '' === $payableTo ? '' : $this->translator->trans(
                'Please make your cheque payable to <b>%name</b>, and send it to the following address :',
                ['%name' => htmlspecialchars($payableTo, \ENT_QUOTES)],
                self::DOMAIN,
                $locale,
            ),
            'store_address' => $this->storeAddress($locale),
            'sign_reminder' => $this->translator->trans('Be sure to sign your cheque !', [], self::DOMAIN, $locale),
            // Written by the merchant in the back office, with its markup, as the Smarty template printed it.
            'instructions' => (string) Cheque::getConfigValue('instructions', '', $locale),
        ]);
    }

    private function template(): string
    {
        return $this->twig->getLoader()->exists(self::THEME_TEMPLATE) ? self::THEME_TEMPLATE : self::MODULE_TEMPLATE;
    }

    /**
     * @return list<string> the lines of the address of the shop, the empty ones left out
     */
    private function storeAddress(string $locale): array
    {
        $country = CountryQuery::create()->findPk((int) ConfigQuery::read('store_country', 0));
        $country?->setLocale($locale);

        $zipAndCity = implode(', ', array_filter([
            (string) ConfigQuery::read('store_zipcode', ''),
            (string) ConfigQuery::read('store_city', ''),
        ], static fn (string $part): bool => '' !== $part));

        return array_values(array_filter([
            (string) ConfigQuery::read('store_name', ''),
            (string) ConfigQuery::read('store_address1', ''),
            (string) ConfigQuery::read('store_address2', ''),
            (string) ConfigQuery::read('store_address3', ''),
            $zipAndCity,
            (string) $country?->getTitle(),
        ], static fn (string $line): bool => '' !== trim($line)));
    }

    private function locale(): string
    {
        return (string) ($this->langService->getLang()?->getLocale() ?? Lang::getDefaultLanguage()->getLocale());
    }
}
