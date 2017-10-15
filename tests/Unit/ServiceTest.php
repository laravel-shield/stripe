<?php

namespace Shield\Stripe\Test\Unit;

use PHPUnit\Framework\Assert;
use Shield\Shield\Contracts\Service;
use Shield\Stripe\Stripe;
use Shield\Testing\TestCase;

/**
 * Class ServiceTest
 *
 * @package \Shield\Stripe\Test
 */
class ServiceTest extends TestCase
{
    /**
     * @var Stripe
     */
    private $service;

    public function setUp()
    {
        parent::setUp();

        $this->service = new Stripe;
    }

    /** @test */
    public function it_is_a_service()
    {
        Assert::assertInstanceOf(Service::class, new Stripe);
    }

    /** @test */
    public function it_can_verify_a_valid_request()
    {
        $token = 'raNd0mk3y';

        $this->app['config']['shield.services.stripe.options.secret'] = $token;

        $content = json_encode(['data' => 'sample content']);

        $request = $this->request($content);

        $time = time();

        $signature = $time . '.' . $content;

        $headers = [
            'Stripe-Signature' => 't=' . $time . ',v1=' . hash_hmac('sha256', $signature, $token)
        ];

        $request->headers->add($headers);

        Assert::assertTrue($this->service->verify($request, collect($this->app['config']['shield.services.stripe.options'])));
    }

    /** @test */
    public function it_will_not_verify_a_bad_request()
    {
        $this->app['config']['shield.services.stripe.options.secret'] = 'good';

        $content = json_encode(['data' => 'sample content']);

        $request = $this->request($content);

        $time = time();

        $signature = $time . '.' . $content;

        $headers = [
            'Stripe-Signature' => 't=' . $time . ',v1=' . hash_hmac('sha256', $signature, 'bad')
        ];

        $request->headers->add($headers);

        Assert::assertFalse($this->service->verify($request, collect($this->app['config']['shield.services.stripe.options'])));
    }

    /** @test */
    public function it_will_fail_if_timestamp_is_over_tolerance()
    {
        $token = 'raNd0mk3y';

        $this->app['config']['shield.services.stripe.options.secret'] = $token;
        $this->app['config']['shield.services.stripe.options.tolerance'] = 60 * 5;

        $content = 'sample content';

        $request = $this->request($content);

        $time = time() - 61 * 5; // 5 seconds over

        $signature = $time . '.' . $content;

        $headers = [
            'Stripe-Signature' => 't=' . $time . ',v1=' . hash_hmac('sha256', $signature, $token)
        ];

        $request->headers->add($headers);

        Assert::assertFalse($this->service->verify($request, collect($this->app['config']['shield.services.stripe.options'])));
    }

    /** @test */
    public function it_has_correct_headers_required()
    {
        Assert::assertArraySubset(['Stripe-Signature'], $this->service->headers());
    }
}
