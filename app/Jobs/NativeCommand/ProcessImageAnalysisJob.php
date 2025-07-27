<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\SendMessage;
use App\Services\Discord\DiscordService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

final class ProcessImageAnalysisJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private array $imageAttachments = [];
    private string $analysisPrompt = '';

    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        parent::__construct($discordUserId, $channelId, $guildId, $messageContent, $command, $commandSlug, $parameters);
        $this->imageAttachments = $parameters['image_attachments'] ?? [];
    }

    public function sendUsageAndExample(?string $additionalInfo = null): void
    {
        $description = "**Usage:** Upload a Discord server screenshot and use:\n`!analyze-server [custom prompt]`\n\n**What it does:**\n• Analyzes Discord server structure from images\n• Extracts themes, categories, and channel organization\n• Generates template commands to recreate similar servers\n\n**Example:**\n`!analyze-server Create a gaming community template`\n\n**Supported formats:** PNG, JPG, GIF, WebP";

        if ($additionalInfo) {
            $description .= "\n\n" . $additionalInfo;
        }

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🖼️ Server Template Generator',
            'embed_description' => $description,
            'embed_color' => 3447003, // Blue
        ]);
    }

    protected function executeCommand(): void
    {
        // Parse the command to extract any custom prompt
        $this->analysisPrompt = $this->parseMessage($this->messageContent);

        // Validate that there are image attachments
        if (empty($this->imageAttachments)) {
            $this->sendUsageAndExample();
            throw new Exception('No image attachments provided.');
        }

        // Send initial message to user
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🖼️ Image Analysis in Progress',
            'embed_description' => '📸 **Analyzing ' . count($this->imageAttachments) . " image(s)...**\n\n🔍 Using ChatGPT Vision to analyze Discord server structure\n⏳ Please wait while I extract themes and generate templates...",
            'embed_color' => 3066993, // Green
        ]);

        // Process each image
        $analysisResults = [];
        foreach ($this->imageAttachments as $index => $attachment) {
            $analysisResult = $this->analyzeImage($attachment, $index + 1);
            if ($analysisResult) {
                $analysisResults[] = $analysisResult;
            }
        }

        if (empty($analysisResults)) {
            throw new Exception('Failed to analyze any images. Please try again with valid Discord server screenshots.');
        }

        // Combine all analysis results and generate templates
        $finalAnalysis = $this->generateServerTemplate($analysisResults);

        if (! $finalAnalysis) {
            throw new Exception('Failed to generate server template from image analysis.');
        }

        // Send analysis results and ask for confirmation
        $this->sendAnalysisResults($finalAnalysis);

        // Store the Discord commands in cache for potential execution
        $cacheKey = "neon_discord_{$this->channelId}_{$this->discordUserId}";
        $cacheData = [
            'discord_commands' => $finalAnalysis['discord_commands'],
            'guild_id' => $this->guildId,
            'synopsis' => $finalAnalysis['synopsis'],
            'analysis_type' => 'image_template',
            'source_images' => count($this->imageAttachments),
        ];
        Cache::put($cacheKey, $cacheData, now()->addMinutes(10)); // Extended time for image analysis
    }

    protected function sendErrorMessage(string $message): void
    {
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '❌ Image Analysis Failed',
            'embed_description' => $message,
            'embed_color' => 15158332, // Red
        ]);
    }

    private function parseMessage(string $message): string
    {
        // Remove the command and return any custom analysis prompt
        $cleaned = trim(str_replace(['!analyze-server', '!template-from-image'], '', $message));

        return $cleaned ?: 'Analyze this Discord server structure and create a similar template';
    }

    private function analyzeImage(array $attachment, int $imageNumber): ?array
    {
        try {
            // Download the image
            $imageData = $this->downloadImage($attachment['url']);
            if (! $imageData) {
                Log::error('Failed to download image', ['url' => $attachment['url']]);

                return null;
            }

            // Prepare the image for ChatGPT Vision
            $base64Image = base64_encode($imageData);
            $mimeType = $this->detectMimeType($attachment['filename']);

            // Create the analysis prompt
            $systemPrompt = $this->buildImageAnalysisPrompt();
            $userPrompt = empty($this->analysisPrompt)
                ? 'Please analyze this Discord server screenshot and extract the server structure, themes, and channel organization.'
                : $this->analysisPrompt;

            // Call ChatGPT Vision API
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $userPrompt,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$base64Image}",
                                    'detail' => 'high',
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 1500,
                'temperature' => 0.3,
            ]);

            $analysisText = $response->choices[0]->message->content ?? null;

            if (! $analysisText) {
                Log::error('Empty response from ChatGPT Vision API', ['image' => $imageNumber]);

                return null;
            }

            return [
                'image_number' => $imageNumber,
                'filename' => $attachment['filename'],
                'analysis' => $analysisText,
                'url' => $attachment['url'],
            ];

        } catch (Exception $e) {
            Log::error('Image analysis failed', [
                'error' => $e->getMessage(),
                'image' => $imageNumber,
                'filename' => $attachment['filename'] ?? 'unknown',
            ]);

            return null;
        }
    }

    private function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::error('Failed to download image', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;

        } catch (Exception $e) {
            Log::error('Image download exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function detectMimeType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png'
        };
    }

    private function buildImageAnalysisPrompt(): string
    {
        return "You are an expert Discord server analyzer with a keen eye for creativity and style! ✨ Your task is to analyze Discord server screenshots and extract detailed structural information to help recreate similar server setups.

When analyzing a Discord server screenshot, focus on:

1. **CATEGORIES** 📁 - Identify all category sections and their themes
2. **CHANNELS** 💬 - List all text and voice channels with their purposes
3. **NAMING PATTERNS** 🎨 - Note consistent naming conventions and emoji usage (pay special attention to creative fonts, special characters, and emoji combinations)
4. **ORGANIZATION** 🏗️ - Understand how channels are grouped and structured
5. **THEMES** 🎭 - Identify the server's purpose, community type, and aesthetic choices
6. **PERMISSIONS** 🔐 - Infer any special channel access or role-based organization
7. **VISUAL STYLE** 🌈 - Notice any creative use of:
   - Unicode fonts (𝕕𝕚𝕗𝕗𝕖𝕣𝕖𝕟𝕥 𝔰𝔱𝔶𝔩𝔢𝔰, 𝓯𝓪𝓷𝓬𝔶 𝓯𝓸𝓷𝓽𝓼, ꜱᴍᴀʟʟ ᴄᴀᴘꜱ)
   - Special characters (┊, ▸, ◦, ◆, ★, ✦, ⟡)
   - Emoji patterns and combinations
   - Creative formatting and aesthetic choices

Provide your analysis in a structured format that includes:
- Server theme/purpose identification with style notes
- Category breakdown with channel listings (include any creative styling)
- Naming pattern analysis (highlight creative fonts and emoji usage)
- Organizational structure insights
- Visual aesthetic observations
- Recommendations for recreating similar setup with style elements

Be specific about channel names, categories, organizational patterns, and especially any creative styling elements you observe. Note creative fonts, special characters, and aesthetic choices that make the server unique and engaging! 🎪";
    }

    private function generateServerTemplate(array $analysisResults): ?array
    {
        try {
            // Combine all analyses into a comprehensive prompt
            $combinedAnalysis = "DISCORD SERVER ANALYSIS RESULTS:\n\n";

            foreach ($analysisResults as $result) {
                $combinedAnalysis .= "=== IMAGE {$result['image_number']}: {$result['filename']} ===\n";
                $combinedAnalysis .= $result['analysis'] . "\n\n";
            }

            // Get available Discord commands for template generation
            $availableCommands = $this->getAvailableDiscordCommands();

            $systemPrompt = "You are a Discord server template generator with exceptional creativity and style! 🎨✨ Based on the provided server analysis, create a comprehensive set of Discord bot commands to recreate a similar server structure with enhanced visual appeal.

{$availableCommands}

CRITICAL RULES:
1. Use ONLY the commands listed above - NEVER invent new commands
2. Follow the EXACT syntax shown for each command
3. Channel names must be Discord-compliant: lowercase, hyphens, no spaces or emojis in actual channel names
4. Create a logical workflow: categories first, then channels within those categories
5. Maintain thematic consistency based on the analyzed server
6. Include both text and voice channels as appropriate

🎪 **CREATIVE ENHANCEMENT GUIDELINES:**
When the analyzed server shows creative styling, enhance your template with:
- **Fun Category Names**: Use creative but Discord-compliant names that capture the theme
- **Stylish Role Names**: Suggest roles that match the server's aesthetic
- **Creative Descriptions**: Use emojis and engaging language in your synopsis
- **Thematic Consistency**: Match the energy and vibe of the original server
- **Community Building**: Focus on creating an engaging, welcoming environment

EXAMPLE CREATIVE ENHANCEMENTS:
- Gaming server: \"esports-arena\", \"battle-grounds\", \"victory-lounge\"
- Art server: \"creative-studio\", \"inspiration-hub\", \"showcase-gallery\"
- Music server: \"beat-laboratory\", \"sound-waves\", \"harmony-hangout\"
- Community server: \"welcome-plaza\", \"chat-central\", \"community-corner\"

RESPONSE FORMAT (JSON):
{
  \"synopsis\": \"🎯 Detailed explanation of the server template being created based on the analyzed images - use emojis and engaging language to describe the vision!\",
  \"discord_commands\": [
    \"!new-category category-name\",
    \"!new-channel channel-name text category-name\",
    \"!new-channel voice-name voice category-name\"
  ]
}

Focus on recreating the essence and organization of the analyzed server while using valid Discord bot commands and enhancing the creative appeal! 🚀";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $combinedAnalysis,
                    ],
                ],
                'max_tokens' => 2000,
                'temperature' => 0.2,
            ]);

            $responseText = $response->choices[0]->message->content ?? null;

            if (! $responseText) {
                return null;
            }

            return $this->parseTemplateResponse($responseText);

        } catch (Exception $e) {
            Log::error('Template generation failed', [
                'error' => $e->getMessage(),
                'analysis_count' => count($analysisResults),
            ]);

            return null;
        }
    }

    private function parseTemplateResponse(string $response): ?array
    {
        try {
            // Extract JSON from the response
            if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
                $jsonContent = $matches[1];
            } elseif (preg_match('/\{.*\}/s', $response, $matches)) {
                $jsonContent = $matches[0];
            } else {
                $jsonContent = $response;
            }

            $parsed = json_decode($jsonContent, true);

            if (! $parsed || ! isset($parsed['synopsis']) || ! isset($parsed['discord_commands'])) {
                Log::error('Invalid template response format', ['response' => $response]);

                return null;
            }

            // Validate commands
            $validatedCommands = $this->validateDiscordCommands($parsed['discord_commands']);

            return [
                'synopsis' => $parsed['synopsis'],
                'discord_commands' => $validatedCommands,
            ];

        } catch (Exception $e) {
            Log::error('Failed to parse template response', [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 500),
            ]);

            return null;
        }
    }

    private function getAvailableDiscordCommands(): string
    {
        // Get the same command list as the main ChatGPT job
        return Cache::remember('neon_available_commands', now()->addHours(1), function () {
            try {
                $commands = DB::table('native_commands')
                    ->where('is_active', true)
                    ->whereNotIn('slug', ['neon', 'analyze-server', 'template-from-image'])
                    ->orderBy('slug')
                    ->get(['slug', 'usage', 'example', 'description']);

                $commandsText = "EXACT COMMAND SYNTAX (USE THESE EXACTLY):\n\n";

                foreach ($commands as $command) {
                    $commandsText .= "**!{$command->slug}**\n";
                    $commandsText .= "  SYNTAX: {$command->usage}\n";
                    if ($command->example) {
                        $commandsText .= "  EXAMPLE: {$command->example}\n";
                    }
                    if ($command->description) {
                        $commandsText .= "  Description: {$command->description}\n";
                    }
                    $commandsText .= "\n";
                }

                return trim($commandsText);
            } catch (Exception $e) {
                Log::error('Failed to get available Discord commands', ['error' => $e->getMessage()]);

                return 'No commands available';
            }
        });
    }

    private function validateDiscordCommands(array $commands): array
    {
        $availableCommands = DB::table('native_commands')
            ->where('is_active', true)
            ->whereNotIn('slug', ['neon', 'analyze-server', 'template-from-image'])
            ->pluck('slug')
            ->toArray();

        $validatedCommands = [];

        foreach ($commands as $command) {
            $commandParts = explode(' ', trim($command));
            if (empty($commandParts)) {
                continue;
            }

            $commandName = ltrim($commandParts[0], '!');

            if (in_array($commandName, $availableCommands)) {
                $validatedCommands[] = $command;
            } else {
                Log::warning('Invalid command generated for image template', [
                    'command' => $command,
                    'extracted_name' => $commandName,
                ]);
            }
        }

        return $validatedCommands;
    }

    private function sendAnalysisResults(array $finalAnalysis): void
    {
        $commandsText = '';
        foreach ($finalAnalysis['discord_commands'] as $index => $command) {
            $commandsText .= ($index + 1) . ". `{$command}`\n";
        }

        // Send the confirmation message
        $baseUrl = config('services.discord.rest_api_url');
        $url = $baseUrl . '/channels/' . $this->channelId . '/messages';

        $embed = [
            'title' => '🖼️ Server Template Generated! ✨',
            'description' => "**📸 Analysis Complete:** Based on the uploaded Discord server screenshot(s), I've created a template to recreate a similar server structure with enhanced creative flair!\n\n**🎨 Template Overview:**\n{$finalAnalysis['synopsis']}\n\n**🔧 Proposed Discord Commands:**\n{$commandsText}\n\n**Ready to build something awesome?** 🚀\nReact with ✅ for **Yes** or ❌ for **No**\n\n*Commands will expire in 10 minutes.*",
            'color' => 7506394, // Purple for image analysis
            'footer' => [
                'text' => 'Image Template Generator • Powered by ChatGPT Vision 🎪',
            ],
        ];

        $discordService = app(DiscordService::class);
        $response = $discordService->post("/channels/{$this->channelId}/messages", [
            'embeds' => [$embed],
        ]);

        if ($response->successful()) {
            $messageData = $response->json();
            $messageId = $messageData['id'];

            // Add reactions to the message
            $this->addReactionToMessage($messageId, '✅');
            $this->addReactionToMessage($messageId, '❌');
        } else {
            Log::error('Failed to send image analysis confirmation message', [
                'channel_id' => $this->channelId,
                'response' => $response->json(),
            ]);
        }
    }

    private function addReactionToMessage(string $messageId, string $emoji): void
    {
        $encodedEmoji = urlencode($emoji);
        $discordService = app(DiscordService::class);

        try {
            $discordService->put("/channels/{$this->channelId}/messages/{$messageId}/reactions/{$encodedEmoji}/@me");
        } catch (Exception $e) {
            Log::warning('Failed to add reaction to message', [
                'message_id' => $messageId,
                'emoji' => $emoji,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
