<?php

declare(strict_types=1);

namespace App\Domain\Notification\Actions;

interface UnsubscribeRecipientAction
{
    /**
     * Unsubscribe recipient from a notification channel
     *
     * @param int $recipientId
     * @param int $notificationTypeId
     * @param string $channel
     *
     * @return void
     */
    public function execute(int $recipientId, int $notificationTypeId, string $channel): void;
}
