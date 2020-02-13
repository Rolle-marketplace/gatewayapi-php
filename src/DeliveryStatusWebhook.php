<?php


namespace nickdnk\GatewayAPI;

use InvalidArgumentException;
use Lcobucci\JWT\Claim;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use nickdnk\GatewayAPI\Exceptions\WebhookException;
use Psr\Http\Message\RequestInterface;

class DeliveryStatusWebhook
{

    /**
     * The message status enumerations.
     * Use these constants to avoid typos in your project.
     *
     * @link https://gatewayapi.com/docs/rest.html#delivery-status-notification
     */
    const STATUS_UNKNOWN       = 'UNKNOWN';
    const STATUS_SCHEDULED     = 'SCHEDULED';
    const STATUS_BUFFERED      = 'BUFFERED';
    const STATUS_EN_ROUTE      = 'ENROUTE';
    const STATUS_DELIVERED     = 'DELIVERED';
    const STATUS_EXPIRED       = 'EXPIRED';
    const STATUS_DELETED       = 'DELETED';
    const STATUS_UNDELIVERABLE = 'UNDELIVERABLE';
    const STATUS_ACCEPTED      = 'ACCEPTED';
    const STATUS_REJECTED      = 'REJECTED';
    const STATUS_SKIPPED       = 'SKIPPED';

    const CHARGE_STATUS_NO_CHARGE   = 'NOCHARGE';
    const CHARGE_STATUS_AUTHORIZED  = 'AUTHORIZED';
    const CHARGE_STATUS_CANCELLED   = 'CANCELLED';
    const CHARGE_STATUS_CAPTURED    = 'CAPTURED';
    const CHARGE_STATUS_FAILED      = 'FAILED';
    const CHARGE_STATUS_REFUNDED    = 'REFUNDED';
    const CHARGE_STATUS_REFUND_FAIL = 'REFUND_FAIL';

    private $messageId, $phoneNumber, $timestamp, $status, $userReference, $chargeStatus, $countryCode, $countryPrefix, $errorDescription, $errorCode;

    /**
     * @param RequestInterface $request
     *
     * @param string           $secret
     *
     * @return static
     * @throws WebhookException
     */
    public static function constructFromRequest(RequestInterface $request, string $secret): self
    {

        $token = $request->getHeaderLine('X-Gwapi-Signature');

        if (!$token) {
            throw new WebhookException('Missing webhook JWT header.');
        }

        try {

            $jwt = (new Parser())->parse($token);

            if (!$jwt->verify(new Sha256(), $secret)) {
                throw new WebhookException('Webhook failed signature validation.');
            }

            /** @var Claim[] $data */
            $data = $jwt->getClaims();

            if (!array_key_exists('id', $data)
                || !array_key_exists('msisdn', $data)
                || !array_key_exists('time', $data)
                || !array_key_exists('status', $data)) {

                throw new WebhookException(
                    'Webhook missing required keys. Got: ' . implode(',', array_keys($data))
                );

            }

            return new self(
                $data['id']->getValue(),
                $data['msisdn']->getValue(),
                $data['time']->getValue(),
                $data['status']->getValue(),
                array_key_exists('userref', $data) ? $data['userref']->getValue() : null,
                array_key_exists('charge_status', $data) ? $data['charge_status']->getValue() : null,
                array_key_exists('country_code', $data) ? $data['country_code']->getValue() : null,
                array_key_exists('country_prefix', $data) ? $data['country_prefix']->getValue() : null,
                array_key_exists('error', $data) ? $data['error']->getValue() : null,
                array_key_exists('code', $data) ? $data['code']->getValue() : null
            );

        } catch (InvalidArgumentException $e) {

            throw new WebhookException('Failed to parse webhook header as JWT.');

        }

    }

    public function __construct(int $messageId, int $phoneNumber, int $timestamp, string $status,
        ?string $userReference, ?string $chargeStatus, ?string $countryCode, ?int $countryPrefix,
        ?string $errorDescription, ?string $errorCode
    )
    {

        $this->messageId = $messageId;
        $this->phoneNumber = $phoneNumber;
        $this->timestamp = $timestamp;
        $this->status = $status;
        $this->userReference = $userReference;
        $this->chargeStatus = $chargeStatus;
        $this->countryCode = $countryCode;
        $this->countryPrefix = $countryPrefix;
        $this->errorDescription = $errorDescription;
        $this->errorCode = $errorCode;
    }

    /**
     * @return int
     */
    public function getMessageId(): int
    {

        return $this->messageId;
    }

    /**
     * @return int
     */
    public function getPhoneNumber(): int
    {

        return $this->phoneNumber;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {

        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {

        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getUserReference(): ?string
    {

        return $this->userReference;
    }

    /**
     * @return string|null
     */
    public function getChargeStatus(): ?string
    {

        return $this->chargeStatus;
    }

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string
    {

        return $this->countryCode;
    }

    /**
     * @return int|null
     */
    public function getCountryPrefix(): ?int
    {

        return $this->countryPrefix;
    }

    /**
     * @return string|null
     */
    public function getErrorDescription(): ?string
    {

        return $this->errorDescription;
    }

    /**
     * @return string|null
     */
    public function getErrorCode(): ?string
    {

        return $this->errorCode;
    }


}