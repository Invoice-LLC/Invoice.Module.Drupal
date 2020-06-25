<?php

namespace Drupal\commerce_payment_invoice\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

include "InvoiceSDK/RestClient.php";
include "InvoiceSDK/CREATE_PAYMENT.php";
include "InvoiceSDK/CREATE_TERMINAL.php";
include "InvoiceSDK/common/ORDER.php";
include "InvoiceSDK/common/SETTINGS.php";

class PaymentOffsiteForm extends BasePaymentOffsiteForm
{
    private $pluginConfig;

    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $payment = $this->entity;
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

        $configuration = $payment_gateway_plugin->getConfiguration();

        $amount = number_format($payment->getAmount()->getNumber(), 2, '.', '');
        $transaction_id = $payment->getOrderId();
        $order = $payment->getOrder();

        $redirect_url = $this->createPayment($amount, $transaction_id);

        $form = $this->buildRedirectForm($form, $form_state, $redirect_url, [], 'get');

        return $form;
    }

    public function createTerminal() {
        $config = $this->getConfig();

        $create_terminal = new \CREATE_TERMINAL("Drupal");

        $this->log(json_encode($create_terminal));

        $restClient = new \RestClient($config['login'], $config['api_key']);

        $this->log("LOGIN ".$config['login']." / KEY: ".$config['api_key']);

        $info = $restClient->CreateTerminal($create_terminal);
        $this->log(json_encode($info));
        if($info == null or $info->error != null) {
            return false;
        } else {
            $this->saveTerminal($info->id);
            return true;
        }
    }

    public function checkOrCreateTerminal() {
        if(empty($this->getTerminal()) or $this->getTerminal() == null) {
            $this->createTerminal();
        }
    }

    public function createPayment($amount, $id) {
        $this->checkOrCreateTerminal();

        $order = new \INVOICE_ORDER($amount);
        $order->id = $id;

        $settings = new \SETTINGS($this->getTerminal());
        $settings->success_url = ( ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);

        $create_payment = new \CREATE_PAYMENT($order, $settings, null);
        $config = $this->getConfig();

        $this->log(json_encode($create_payment));
        $restClient = new \RestClient($config['login'], $config['api_key']);

        $info = $restClient->CreatePayment($create_payment);

        if($info == null or $info->error != null) return false;
        return $info->payment_url;
    }

    public function saveTerminal($id) {
        file_put_contents("invoice_tid", $id);
    }

    public function getTerminal() {
        return file_get_contents("invoice_tid");
    }
    public function log($log) {
        $fp = fopen('invoice_payment.log', 'a+');
        fwrite($fp, "\n".$log);
        fclose($fp);
    }
    public function getConfig() {
        $payment = $this->entity;
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
        $configuration = $payment_gateway_plugin->getConfiguration();

        return $configuration;
    }
}