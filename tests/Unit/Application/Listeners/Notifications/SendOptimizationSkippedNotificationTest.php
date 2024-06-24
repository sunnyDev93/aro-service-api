<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Notifications;

use App\Application\Events\OptimizationSkipped;
use App\Application\Listeners\Notifications\SendOptimizationSkippedNotification;
use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Notification\Enums\NotificationTypeEnum;
use App\Domain\Notification\Queries\NotificationTypeRecipientsQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Services\Notification\Senders\NotificationSender;
use App\Infrastructure\Services\Notification\Senders\NotificationSenderParams;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Notification\RecipientFactory;
use Tests\Tools\Factories\OfficeFactory;

class SendOptimizationSkippedNotificationTest extends TestCase
{
    private MockInterface|FeatureFlagService $mockFeatureFlagService;
    private MockInterface|NotificationTypeRecipientsQuery $mockNotificationRecipientsQuery;
    private MockInterface|NotificationSender $mockNotificationSender;

    private Office $office;
    private CarbonInterface $date;

    private SendOptimizationSkippedNotification $service;
    private OptimizationSkipped $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFeatureFlagService = $this->mock(FeatureFlagService::class);
        $this->mockNotificationRecipientsQuery = $this->mock(NotificationTypeRecipientsQuery::class);
        $this->mockNotificationSender = $this->mock(NotificationSender::class);

        $this->office = OfficeFactory::make();
        $this->date = Carbon::today();

        $this->event = new OptimizationSkipped(
            $this->office,
            $this->date,
            new Exception($this->faker->sentence()),
        );

        $this->service = new SendOptimizationSkippedNotification(
            $this->mockFeatureFlagService,
            $this->mockNotificationRecipientsQuery,
            $this->mockNotificationSender,
        );
    }

    /**
     * @test
     */
    public function it_does_not_send_notifications_when_feature_flag_is_disabled(): void
    {
        $this->mockNotificationRecipientsQuery
            ->shouldNotReceive('get');
        $this->mockNotificationSender
            ->shouldNotReceive('send');

        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->andReturnFalse();

        Log::shouldReceive('notice')
            ->once();

        $this->service->handle($this->event);
    }

    /**
     * @test
     */
    public function it_logs_error_when_notification_service_throws_exception(): void
    {
        $this->setMockFeatureFlagServiceExpectations();

        $this->mockNotificationRecipientsQuery
            ->shouldReceive('get')
            ->once()
            ->with(NotificationTypeEnum::OPTIMIZATION_SKIPPED)
            ->andReturn(collect(RecipientFactory::many(2)));

        $this->mockNotificationSender
            ->shouldReceive('send')
            ->once()
            ->andThrow(new Exception('Service failed'));

        Log::shouldReceive('error')
            ->once();

        $this->service->handle($this->event);
    }

    /**
     * @test
     */
    public function it_sends_notifications(): void
    {
        $this->setMockFeatureFlagServiceExpectations();

        $this->mockNotificationRecipientsQuery
            ->shouldReceive('get')
            ->once()
            ->with(NotificationTypeEnum::OPTIMIZATION_SKIPPED)
            ->andReturn(collect(RecipientFactory::many(2)));

        $this->mockNotificationSender
            ->shouldReceive('send')
            ->once()
            ->withArgs(function (NotificationSenderParams $params) {
                return $params->title === __('messages.notifications.optimization_skipped.title')
                    && $params->message === __('messages.notifications.optimization_skipped.message', [
                        'office' => $this->office->getName(),
                        'date' => $this->date->toDateString(),
                        'exception' => $this->event->exception->getMessage(),
                    ])
                    && $params->recipients->count() === 2;
            });

        $this->service->handle($this->event);
    }

    private function setMockFeatureFlagServiceExpectations(): void
    {
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->andReturnTrue();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockFeatureFlagService);
        unset($this->mockNotificationRecipientsQuery);
        unset($this->mockNotificationSender);
        unset($this->mockJob);
        unset($this->service);
        unset($this->event);
    }
}
