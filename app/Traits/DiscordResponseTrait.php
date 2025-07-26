<?php

declare(strict_types=1);

namespace App\Traits;

use App\Helpers\Discord\SendMessage;

trait DiscordResponseTrait
{
    /**
     * Send a success message with embed.
     */
    protected function sendSuccessMessage(string $title, string $description, int $color = 3066993): void
    {
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => "✅ {$title}",
            'embed_description' => $description,
            'embed_color' => $color,
        ]);
    }

    /**
     * Send an error message without embed.
     */
    protected function sendErrorMessage(string $message): void
    {
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => false,
            'response' => "❌ {$message}",
        ]);
    }

    /**
     * Send batch operation results (success and failures).
     */
    protected function sendBatchResults(
        string $action,
        array $successfulItems,
        array $failedItems,
        string $itemType = 'item'
    ): void {
        $successMessage = count($successfulItems) > 0
            ? "✅ {$action} successful for: " . implode(', ', $successfulItems)
            : '';

        $errorMessage = count($failedItems) > 0
            ? "❌ Failed {$action} for: " . implode(', ', $failedItems)
            : '';

        $color = count($successfulItems) > 0 ? 3066993 : 15158332; // Green or Red

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => "🔹 {$action} Results",
            'embed_description' => trim($successMessage . "\n" . $errorMessage),
            'embed_color' => $color,
        ]);
    }

    /**
     * Send a warning message.
     */
    protected function sendWarningMessage(string $message): void
    {
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '⚠️ Warning',
            'embed_description' => $message,
            'embed_color' => 16776960, // Yellow
        ]);
    }

    /**
     * Send an info message.
     */
    protected function sendInfoMessage(string $title, string $description): void
    {
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => "ℹ️ {$title}",
            'embed_description' => $description,
            'embed_color' => 3447003, // Blue
        ]);
    }

    /**
     * Send user action confirmation (ban, kick, mute, etc.).
     */
    protected function sendUserActionConfirmation(string $action, string $userId, string $emoji = '🔨'): void
    {
        $this->sendSuccessMessage(
            "User {$action}",
            "{$emoji} <@{$userId}> has been {$action}.",
            15158332 // Red for moderation actions
        );
    }

    /**
     * Send channel action confirmation.
     */
    protected function sendChannelActionConfirmation(string $action, string $channelId, string $details = ''): void
    {
        $description = "🔧 <#{$channelId}> has been {$action}.";
        if ($details) {
            $description .= "\n{$details}";
        }

        $this->sendSuccessMessage("Channel {$action}", $description);
    }

    /**
     * Send role action confirmation.
     */
    protected function sendRoleActionConfirmation(string $action, string $roleName, string $details = ''): void
    {
        $description = "🎭 Role '{$roleName}' has been {$action}.";
        if ($details) {
            $description .= "\n{$details}";
        }

        $this->sendSuccessMessage("Role {$action}", $description);
    }

    /**
     * Send a list-style message with multiple items.
     */
    protected function sendListMessage(string $title, array $items, int $color = 3447003): void
    {
        $description = '';
        foreach ($items as $item) {
            $description .= "• {$item}\n";
        }

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => $title,
            'embed_description' => trim($description),
            'embed_color' => $color,
        ]);
    }

    /**
     * Send permission denied message with specific context.
     */
    protected function sendPermissionDenied(string $action): void
    {
        $this->sendErrorMessage("You do not have permission to {$action} in this server.");
    }

    /**
     * Send not found message for various Discord entities.
     */
    protected function sendNotFound(string $entityType, string $identifier): void
    {
        $this->sendErrorMessage("{$entityType} '{$identifier}' not found.");
    }

    /**
     * Send API error message.
     */
    protected function sendApiError(string $action): void
    {
        $this->sendErrorMessage("Failed to {$action}. Ensure the bot has the correct permissions.");
    }
}
