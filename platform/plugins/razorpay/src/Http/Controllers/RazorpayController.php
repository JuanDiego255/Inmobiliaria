<?php

namespace Botble\Razorpay\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestError;

class RazorpayController extends BaseController
{
    public function webhook(Request $request)
    {
        if (
            $request->input('event') === 'order.paid'
            && $request->input('payload.order.entity.status') === 'paid'
        ) {
            $api = new Api(
                get_payment_setting('key', RAZORPAY_PAYMENT_METHOD_NAME),
                get_payment_setting('secret', RAZORPAY_PAYMENT_METHOD_NAME)
            );

            try {
                $order = $api->order->fetch($request->input('payload.payment.entity.order_id'));

                if ($order['status'] === 'paid') {
                    $chargeId = $request->input('payload.payment.entity.id');
                    $payment = Payment::query()
                        ->where('charge_id', $chargeId)
                        ->first();

                    if ($payment) {
                        $payment->status = PaymentStatusEnum::COMPLETED;
                        $payment->save();

                        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                            'charge_id' => $payment->charge_id,
                            'order_id' => $payment->order_id,
                        ]);
                    }
                }
            } catch (BadRequestError) {
                return;
            }
        }
    }
}
