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
use Symfony\Component\Form\FormFactoryInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

/**
 * Class HookManager.
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class HookManager extends BaseHook
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
    ) {
        parent::__construct();
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $locale = $this->getLang()->getLocale();

        $form = $this->formFactory->createNamed(
            ConfigurationForm::getName(),
            ConfigurationForm::class,
            [
                'payable_to' => Cheque::getConfigValue('payable_to', ''),
                'instructions' => Cheque::getConfigValue('instructions', '', $locale),
            ]
        );

        $event->add($this->render('module_configuration.html.twig', [
            'form' => $form->createView(),
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
