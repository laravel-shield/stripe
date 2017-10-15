<?php

namespace Shield\Stripe;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Shield\Shield\Contracts\Service;
use Stripe\Error\SignatureVerification;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Class Service
 *
 * @package \Shield\Stripe
 */
class Stripe implements Service
{
    public function verify(Request $request, Collection $config): bool
    {
        try {
            Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $config->get('secret'),
                $config->get('tolerance', Webhook::DEFAULT_TOLERANCE)
            );
        } catch (UnexpectedValueException | SignatureVerification $exception) {
            return false;
        }

        return true;
    }

    public function headers(): array
    {
        return ['Stripe-Signature'];
    }
}
