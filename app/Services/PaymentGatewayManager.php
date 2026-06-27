<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\GatewayUnavailableException;
use App\Exceptions\UnknownGatewayException;
use App\Models\PaymentGateway;
use Illuminate\Support\Collection;

class PaymentGatewayManager
{
    /** @var Collection<string, PaymentGatewayInterface> */
    private Collection $gateways;

    /**
     * @param  array<int, PaymentGatewayInterface>  $gateways
     */
    public function __construct(array $gateways)
    {
        $this->gateways = collect($gateways)->keyBy(
            fn (PaymentGatewayInterface $gateway) => $gateway->getCode()
        );
    }

    public function resolve(string $paymentMethod): PaymentGatewayInterface
    {
        $gateway = $this->gateways->get($paymentMethod);

        if ($gateway === null) {
            throw new UnknownGatewayException($paymentMethod);
        }

        $config = PaymentGateway::query()->where('code', $paymentMethod)->first();

        if ($config === null || ! $config->is_enabled) {
            throw new GatewayUnavailableException($paymentMethod);
        }

        /** @var array{env_prefix?: string|null, api_key?: string|null, secret?: string|null} $gatewayConfig */
        $gatewayConfig = config("payments.gateways.{$paymentMethod}", []);

        if (! empty($gatewayConfig['env_prefix'])) {
            if (blank($gatewayConfig['api_key'] ?? null) || blank($gatewayConfig['secret'] ?? null)) {
                throw new GatewayUnavailableException($paymentMethod);
            }
        }

        return $gateway;
    }

    /**
     * @return array<int, string>
     */
    public function enabledCodes(): array
    {
        return PaymentGateway::query()
            ->where('is_enabled', true)
            ->pluck('code')
            ->all();
    }
}
