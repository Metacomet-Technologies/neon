<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Twitch;

use App\Models\TwitchEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class EventSubWebhookController
{
    public string $hmacPrefix = 'sha256=';

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
    {
        $secret = 'abcdefghijk';

        // Validate the request is authentic by verifying the signature of the payload
        // Get the message id, timestamp, and signature from the headers
        $twitchMessageId = $request->header('Twitch-Eventsub-Message-Id');
        $twitchMessageTimestamp = $request->header('Twitch-Eventsub-Message-Timestamp');
        $twitchMessageSignature = $request->header('Twitch-Eventsub-Message-Signature');

        if (! $twitchMessageId || ! $twitchMessageTimestamp || ! $twitchMessageSignature) {
            Log::error('Missing required headers', [
                'message_id' => $twitchMessageId,
                'timestamp' => $twitchMessageTimestamp,
                'signature' => $twitchMessageSignature,
            ]);

            return response()->json(['error' => 'Missing required headers'], 400);
        }

        Log::info('Webhook received', [
            'message_id' => $twitchMessageId,
            'timestamp' => $twitchMessageTimestamp,
        ]);

        // Create the expected message signature
        $messageSignature = $this->hmacPrefix . hash_hmac('sha256', $twitchMessageId . $twitchMessageTimestamp . $request->getContent(), $secret);

        // Compare the message signature to the signature from the headers
        if (! hash_equals((string) $twitchMessageSignature, $messageSignature)) {
            // Invalid signature - ignore the message
            Log::error('Invalid signature', [
                'message_id' => $twitchMessageId,
                'timestamp' => $twitchMessageTimestamp,
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // If the message is a verification request, return the challenge
        $twitchMessageType = $request->header('Twitch-Eventsub-Message-Type');
        if ($twitchMessageType === 'webhook_callback_verification') {
            $challenge = $request->json('challenge');
            Log::info('Verification request received', [
                'message_id' => $twitchMessageId,
                'timestamp' => $twitchMessageTimestamp,
                'challenge' => $challenge,
            ]);

            return response($challenge, 200, ['Content-Type' => 'text/plain']);
        }

        $existingEvent = TwitchEvent::where('event_id', $twitchMessageId)->first();
        if ($existingEvent) {
            Log::info('Event already processed', [
                'message_id' => $twitchMessageId,
                'timestamp' => $twitchMessageTimestamp,
            ]);

            return response()->json(['message' => 'Event already processed'], 200);
        }

        $event = TwitchEvent::create([
            'event_id' => $twitchMessageId,
            'event_timestamp' => $twitchMessageTimestamp,
            'event_type' => $request->header('Twitch-Eventsub-Subscription-Type'),
            'event_data' => $request->json()->all(),
            'is_processed' => false,
        ]);

        // TODO: Dispatch the event to a queue for processing

        Log::info('Event saved', [
            'message_id' => $twitchMessageId,
            'timestamp' => $twitchMessageTimestamp,
        ]);

        // Acknowledge the message
        return response()->json(['message' => 'Event received'], 200);
    }
}
