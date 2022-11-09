<?php

namespace Drupal\commerce_payment_invoice\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderStorage;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @CommercePaymentGateway(
 *   id = "invoice_commerce",
 *   label = "Invoice",
 *   display_label = "Invoice",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_payment_invoice\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 * )
 */
class OffsiteRedirect extends OffsitePaymentGatewayBase
{
    public function defaultConfiguration() {
        return [
            'api_key' => '',
            'login' => '',
        ] + parent::defaultConfiguration();
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['api_key'] = [
            '#type' => 'textfield',
            '#title' => "API Key",
            '#default_value' => $this->configuration['api_key'],
            '#required' => true
        ];

        $form['login'] = [
            '#type' => 'textfield',
            '#title' => "Merchant ID",
            '#default_value' => $this->configuration['login'],
            '#required' => true
        ];

        return $form;
    }

    public function onNotify(Request $request) {
        $postData = file_get_contents('php://input');
        $notification = json_decode($postData, true);

        $type = $notification["notification_type"];
        $id = strstr($notification["order"]["id"], "-", true);

        $signature = $notification["signature"];

        if($signature != $this->getSignature($notification["id"], $notification["status"], $this->configuration['api_key'])) {
            return new Response('ok', 400);
        }

        if($type == "pay") {

            if($notification["status"] == "successful") {
                $this->setPaymentStatus(true,$id, $notification["order"]["amount"]);
                return new Response('ok');
            }
            if($notification["status"] == "error") {
                $this->setPaymentStatus(false,$id, $notification["order"]["amount"]);
                return new Response('failed');
            }
        }

        return new Response('null');
    }

    public function onReturn(OrderInterface $order, Request $request) {
        \Drupal::messenger()->addStatus('Payment was processed');
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);
        if (!$form_state->getErrors()) {
            $values                                 = $form_state->getValue($form['#parents']);
            $this->configuration['api_key']        = $values['api_key'];
            $this->configuration['login']        = $values['login'];
        }
    }

    public function setPaymentStatus($paid, $id, $amount) {
        if($paid) {
            $order_storage = $this->entityTypeManager->getStorage('commerce_order');
            $order = $order_storage->load($id);

            if($order == null) {
                echo 'order not found';
                return;
            }

            $order->set('state', 'completed');
            $order->save();

            $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
            $payment = $payment_storage->create([
                'state' => 'completed',
                'amount' => $order->getTotalPrice(),
                'payment_gateway' => $this->entityId,
                'order_id' => $order->id(),
                'test' => false,
                'remote_id' => $id,
                'remote_state' => 'PAYED',
                'authorized' => \Drupal::time()->getRequestTime(),
            ]);
            $payment->save();
        } else {
            echo 'FAIL';
        }
    }

    public function getSignature($id, $status, $key) {
        return md5($id.$status.$key);
    }
}