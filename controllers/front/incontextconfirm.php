<?php
/**
 * 2017 Thirty Bees
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use PayPalModule\PayPalLogos;
use PayPalModule\PayPalRestApi;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/../../paypal.php';

/**
 * Class paypalincontextconfirmModuleFrontController
 */
class paypalincontextconfirmModuleFrontController extends \ModuleFrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $display_column_left */
    public $display_column_left = false;
    // @codingStandardsIgnoreEnd

    /** @var \PayPal $module */
    public $module;

    /** @var bool $ssl */
    public $ssl = true;

    /**
     * Initialize content
     */
    public function initContent()
    {
        parent::initContent();

        if (\Tools::isSubmit('confirm')) {
            $this->confirmOrder();

            return;
        }

        if (\Tools::isSubmit('update')) {
            $this->updateOrder();
        }

        $this->assignCartSummary();

        $params = [
            'confirm' => true,
            'PayerID' => \Tools::getValue('PayerID'),
            'paymentID' => \Tools::getValue('paymentID'),
        ];

        $this->context->smarty->assign([
            'confirm_form_action' => $this->context->link->getModuleLink($this->module->name, 'incontextconfirm', $params, true),
        ]);

        $this->setTemplate('order-summary.tpl');
    }

    /**
     * Assign cart summary
     */
    public function assignCartSummary()
    {
        $currency = new \Currency((int) $this->context->cart->id_currency);

        $this->context->smarty->assign([
            'total' => \Tools::displayPrice($this->context->cart->getOrderTotal(true), $currency),
            'logos' => PayPalLogos::getLogos($this->module->getLocale()),
            'use_mobile' => (bool) $this->context->getMobileDevice(),
            'address_shipping' => new \Address($this->context->cart->id_address_delivery),
            'address_billing' => new \Address($this->context->cart->id_address_invoice),
            'cart' => $this->context->cart,
            'patternRules' => ['avoid' => []],
            'cart_image_size' => 'cart_default',
        ]);

        $this->context->smarty->assign([
            'paypal_cart_summary' => $this->module->display(_PS_MODULE_DIR_.'paypal/paypal.php', 'views/templates/hook/paypal_cart_summary.tpl'),
        ]);
    }

    protected function confirmOrder()
    {
        $payerId = \Tools::getValue('PayerID');
        $paymentId = \Tools::getValue('paymentID');

        $rest = new PayPalRestApi();
        $payment = $rest->executePayment($payerId, $paymentId);

        $transaction = [
            'id_transaction' => $payment->id,
            'payment_status' => $payment->state,
            'currency' => $payment->transactions[0]->amount->currency,
            'payment_date' => date("Y-m-d H:i:s"),
            'total_paid' => $payment->transactions[0]->amount->total,
            'id_invoice' => 0,
            'shipping' => 0,
        ];

        if ($this->module->validateOrder(
            $this->context->cart->id,
            (int) \Configuration::get('PS_OS_PAYMENT'),
            $payment->transactions[0]->amount->total,
            $payment->payer->payment_method,
            null,
            $transaction,
            null,
            false,
            $this->context->cart->secure_key
        )) {
            $params = [
                'id_cart' => $this->context->cart->id,
                'secure_key' => $this->context->cart->secure_key,
                'id_module' => $this->module->id,
            ];

            \Tools::redirectLink($this->context->link->getModuleLink($this->module->name, 'orderconfirmation', $params, true));

            return;
        }

        // FIXME: file missing
        \Tools::redirectLink($this->context->link->getModuleLink($this->module->name, 'error', [], true));
    }

    protected function updateOrder()
    {
        // TODO: implement
    }

    protected function cancelOrder()
    {
//        $this->module->cancelOrder();
    }
}
