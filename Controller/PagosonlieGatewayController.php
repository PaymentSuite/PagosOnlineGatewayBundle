<?php

/**
 * PagosonlineGatewayBundle for Symfony2
 *
 * This Bundle is part of Symfony2 Payment Suite
 *
 * @package PagosonlineGatewayBundle
 *
 */

namespace Scastells\PagosonlineGatewayBundle\Controller;

use Mmoreram\PaymentCoreBundle\Exception\PaymentOrderNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


/**
 * PagosonlineGatewayController
 *
 */
class PagosonlineGatewayController extends Controller
{

    /**
     * Payment execution
     *
     * @param Request $request Request element
     *
     * @return RedirectResponse
     *
     * @Method("POST")
     * @Template()
     */
    public function executeAction(Request $request)
    {
        $paymentMethod = new DineromailMethod;
        $paymentBridge = $this->get('payment.bridge');

        /**
         * New order from cart must be created right here
         */
        $this->get('payment.event.dispatcher')->notifyPaymentOrderLoad($paymentBridge, $paymentMethod);


        /**
         * Order Not found Exception must be thrown just here
         */
        if (!$paymentBridge->getOrder()) {

            throw new PaymentOrderNotFoundException;
        }

        $dineromailTransactionId = $paymentBridge->getOrderId() . '#' . date('Ymdhis');
        $paymentMethod->setDineromailTransactionId($dineromailTransactionId);

        /**
         * Loading success route for returning from dineroMail
         */
        $redirectSuccessUrl = $this->container->getParameter('pagosonlinegateway.success.route');
        $redirectSuccessAppend = $this->container->getParameter('pagosonlinegateway.success.order.append');
        $redirectSuccessAppendField = $this->container->getParameter('pagosonlinegateway.success.order.field');

        $redirectSuccessData    = $redirectSuccessAppend
                                ? array(
                                    $redirectSuccessAppendField => $this->get('payment.bridge')->getOrderId(),
                                )
                                : array();

        $successRoute = $this->generateUrl($redirectSuccessUrl, $redirectSuccessData, true);


        /**
         * Loading fail route for returning from dineroMail
         */
        $redirectFailUrl = $this->container->getParameter('dineromail.fail.route');
        $redirectFailAppend = $this->container->getParameter('dineromail.fail.order.append');
        $redirectFailAppendField = $this->container->getParameter('dineromail.fail.order.field');

        $redirectFailData    = $redirectFailAppend
                                ? array(
                                    $redirectFailAppendField => $this->get('payment.bridge')->getOrderId(),
                                )
                                : array();

        $failRoute = $this->generateUrl($redirectFailUrl, $redirectFailData, true);

        $this->get('payment.event.dispatcher')->notifyPaymentOrderDone($paymentBridge, $paymentMethod);

        /**
         * Build form
         */
        $formView = $this
            ->get('dineromail.form.type.wrapper')
            ->buildForm($successRoute, $failRoute, $dineromailTransactionId)
            ->getForm()
            ->createView();

        return array(

            'dineromail_form' => $formView,
        );
    }
}
