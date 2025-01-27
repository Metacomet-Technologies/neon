<?php

namespace App\Enums;

enum DiscordPermissionEnum: int
{
    case CREATE_INSTANT_INVITE = 0x0000000000000001; // 1 << 0
    case KICK_MEMBERS = 0x0000000000000002; // 1 << 1
    case BAN_MEMBERS = 0x0000000000000004; // 1 << 2
    case ADMINISTRATOR = 0x0000000000000008; // 1 << 3
    case MANAGE_CHANNELS = 0x0000000000000010; // 1 << 4
    case MANAGE_GUILD = 0x0000000000000020; // 1 << 5
    case ADD_REACTIONS = 0x0000000000000040; // 1 << 6
    case VIEW_AUDIT_LOG = 0x0000000000000080; // 1 << 7
    case PRIORITY_SPEAKER = 0x0000000000000100; // 1 << 8
    case STREAM = 0x0000000000000200; // 1 << 9
    case VIEW_CHANNEL = 0x0000000000000400; // 1 << 10
    case SEND_MESSAGES = 0x0000000000000800; // 1 << 11
    case SEND_TTS_MESSAGES = 0x0000000000001000; // 1 << 12
    case MANAGE_MESSAGES = 0x0000000000002000; // 1 << 13
    case EMBED_LINKS = 0x0000000000004000; // 1 << 14
    case ATTACH_FILES = 0x0000000000008000; // 1 << 15
    case READ_MESSAGE_HISTORY = 0x0000000000010000; // 1 << 16
    case MENTION_EVERYONE = 0x0000000000020000; // 1 << 17
    case USE_EXTERNAL_EMOJIS = 0x0000000000040000; // 1 << 18
    case VIEW_GUILD_INSIGHTS = 0x0000000000080000; // 1 << 19
    case CONNECT = 0x0000000000100000; // 1 << 20
    case SPEAK = 0x0000000000200000; // 1 << 21
    case MUTE_MEMBERS = 0x0000000000400000; // 1 << 22
    case DEAFEN_MEMBERS = 0x0000000000800000; // 1 << 23
    case MOVE_MEMBERS = 0x0000000001000000; // 1 << 24
    case USE_VAD = 0x0000000002000000; // 1 << 25
    case CHANGE_NICKNAME = 0x0000000004000000; // 1 << 26
    case MANAGE_NICKNAMES = 0x0000000008000000; // 1 << 27
    case MANAGE_ROLES = 0x0000000010000000; // 1 << 28
    case MANAGE_WEBHOOKS = 0x0000000020000000; // 1 << 29
    case MANAGE_GUILD_EXPRESSIONS = 0x0000000040000000; // 1 << 30
    case USE_APPLICATION_COMMANDS = 0x0000000080000000; // 1 << 31
    case REQUEST_TO_SPEAK = 0x0000000100000000; // 1 << 32
    case MANAGE_EVENTS = 0x0000000200000000; // 1 << 33
    case MANAGE_THREADS = 0x0000000400000000; // 1 << 34
    case CREATE_PUBLIC_THREADS = 0x0000000800000000; // 1 << 35
    case CREATE_PRIVATE_THREADS = 0x0000001000000000; // 1 << 36
    case USE_EXTERNAL_STICKERS = 0x0000002000000000; // 1 << 37
    case SEND_MESSAGES_IN_THREADS = 0x0000004000000000; // 1 << 38
    case USE_EMBEDDED_ACTIVITIES = 0x0000008000000000; // 1 << 39
    case MODERATE_MEMBERS = 0x0000010000000000; // 1 << 40
    case VIEW_CREATOR_MONETIZATION_ANALYTICS = 0x0000020000000000; // 1 << 41
    case USE_SOUNDBOARD = 0x0000040000000000; // 1 << 42
    case CREATE_GUILD_EXPRESSIONS = 0x0000080000000000; // 1 << 43
    case CREATE_EVENTS = 0x0000100000000000; // 1 << 44
    case USE_EXTERNAL_SOUNDS = 0x0000200000000000; // 1 << 45
    case SEND_VOICE_MESSAGES = 0x0000400000000000; // 1 << 46
    case SEND_POLLS = 0x0002000000000000; // 1 << 49
    case USE_EXTERNAL_APPS = 0x0004000000000000; // 1 << 50

    /**
     * Get the description of the permission.
     */
    public function description(): string
    {
        return match ($this) {
            self::CREATE_INSTANT_INVITE => 'Allows creation of instant invites',
            self::KICK_MEMBERS => 'Allows kicking members',
            self::BAN_MEMBERS => 'Allows banning members',
            self::ADMINISTRATOR => 'Allows all permissions and bypasses channel permission overwrites',
            self::MANAGE_CHANNELS => 'Allows management and editing of channels',
            self::MANAGE_GUILD => 'Allows management and editing of the guild',
            self::ADD_REACTIONS => 'Allows for the addition of reactions to messages',
            self::VIEW_AUDIT_LOG => 'Allows for viewing of audit logs',
            self::PRIORITY_SPEAKER => 'Allows for using priority speaker in a voice channel',
            self::STREAM => 'Allows the user to go live',
            self::VIEW_CHANNEL => 'Allows guild members to view a channel, which includes reading messages in text channels and joining voice channels',
            self::SEND_MESSAGES => 'Allows for sending messages in a channel and creating threads in a forum (does not allow sending messages in threads)',
            self::SEND_TTS_MESSAGES => 'Allows for sending of /tts messages',
            self::MANAGE_MESSAGES => 'Allows for deletion of other users messages',
            self::EMBED_LINKS => 'Links sent by users with this permission will be auto-embedded',
            self::ATTACH_FILES => 'Allows for uploading images and files',
            self::READ_MESSAGE_HISTORY => 'Allows for reading of message history',
            self::MENTION_EVERYONE => 'Allows for using the @everyone tag to notify all users in a channel, and the @here tag to notify all online users in a channel',
            self::USE_EXTERNAL_EMOJIS => 'Allows the usage of custom emojis from other servers',
            self::VIEW_GUILD_INSIGHTS => 'Allows for viewing guild insights',
            self::CONNECT => 'Allows for joining of a voice channel',
            self::SPEAK => 'Allows for speaking in a voice channel',
            self::MUTE_MEMBERS => 'Allows for muting members in a voice channel',
            self::DEAFEN_MEMBERS => 'Allows for deafening of members in a voice channel',
            self::MOVE_MEMBERS => 'Allows for moving of members between voice channels',
            self::USE_VAD => 'Allows for using voice-activity-detection in a voice channel',
            self::CHANGE_NICKNAME => 'Allows for modification of own nickname',
            self::MANAGE_NICKNAMES => 'Allows for modification of other users nicknames',
            self::MANAGE_ROLES => 'Allows management and editing of roles',
            self::MANAGE_WEBHOOKS => 'Allows management and editing of webhooks',
            self::MANAGE_GUILD_EXPRESSIONS => 'Allows for editing and deleting emojis, stickers, and soundboard sounds created by all users',
            self::USE_APPLICATION_COMMANDS => 'Allows members to use application commands, including slash commands and context menu commands',
            self::REQUEST_TO_SPEAK => 'Allows for requesting to speak in stage channels',
            self::MANAGE_EVENTS => 'Allows for editing and deleting scheduled events created by all users',
            self::MANAGE_THREADS => 'Allows for deleting and archiving threads, and viewing all private threads',
            self::CREATE_PUBLIC_THREADS => 'Allows for creating public and announcement threads',
            self::CREATE_PRIVATE_THREADS => 'Allows for creating private threads',
            self::USE_EXTERNAL_STICKERS => 'Allows the usage of custom stickers from other servers',
            self::SEND_MESSAGES_IN_THREADS => 'Allows for sending messages in threads',
            self::USE_EMBEDDED_ACTIVITIES => 'Allows for using Activities (applications with the EMBEDDED flag) in a voice channel',
            self::MODERATE_MEMBERS => 'Allows for timing out users to prevent them from sending or reacting to messages in chat and threads, and from speaking in voice and stage channels',
            self::VIEW_CREATOR_MONETIZATION_ANALYTICS => 'Allows for viewing role subscription insights',
            self::USE_SOUNDBOARD => 'Allows for using soundboard in a voice channel',
            self::CREATE_GUILD_EXPRESSIONS => 'Allows for creating emojis, stickers, and soundboard sounds, and editing and deleting those created by the current user',
            self::CREATE_EVENTS => 'Allows for creating scheduled events, and editing and deleting those created by the current user',
            self::USE_EXTERNAL_SOUNDS => 'Allows the usage of custom soundboard sounds from other servers',
            self::SEND_VOICE_MESSAGES => 'Allows sending voice messages',
            self::SEND_POLLS => 'Allows sending polls',
            self::USE_EXTERNAL_APPS => 'Allows user-installed apps to send public responses',
        };
    }
}
