<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cheque\Hook;

use Cheque\Cheque;
use Cheque\Form\ConfigurationForm;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;

/**
 * Class HookManager.
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class HookManager extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfiguration'],
            ],
            'order-placed.additional-payment-info' => [
                ['type' => 'front', 'method' => 'onAdditionalPaymentInfo'],
            ],
        ];
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $locale = $this->getLang()->getLocale();

        $form = $this->formFactory->createForm(ConfigurationForm::getName(), data: [
            'payable_to' => Cheque::getConfigValue('payable_to', ''),
            'instructions' => Cheque::getConfigValue('instructions', '', $locale),
        ]);

        $event->add($this->render('module_configuration.html.twig', [
            'form' => $form->createView()->getView(),
        ]));
    }

    public function onAdditionalPaymentInfo(HookRenderEvent $event): void
    {
        $content = $this->render('order-placed.additional-payment-info.html', [
            'placed_order_id' => $event->getArgument('placed_order_id'),
        ]);

        $event->add($content);
    }
}
