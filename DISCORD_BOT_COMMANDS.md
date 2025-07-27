# Discord Bot Commands Reference

This document provides a comprehensive list of all available Discord bot commands with their correct syntax and usage examples.

## Table of Contents

- [AI Assistant](#ai-assistant)
- [Channel Management](#channel-management)
- [Category Management](#category-management)
- [Role Management](#role-management)
- [User Management](#user-management)
- [Voice Channel Management](#voice-channel-management)
- [Message Management](#message-management)
- [Event Management](#event-management)
- [Server Management](#server-management)
- [Utility Commands](#utility-commands)

---

## AI Assistant

### !neon
**Description:** AI assistant powered by ChatGPT that helps you interact with Discord server management using natural language queries.

**Usage:** `!neon <your question or request>`

**Example:** `!neon create some channels for new members to chat`

---

## Channel Management

### !assign-channel
**Description:** Assigns a channel to a category.

**Usage:** `!assign-channel <channel-id|channel-name> <category-id|category-name>`

**Example:** `!assign-channel 123456789012345678 987654321098765432`

### !delete-channel
**Description:** Deletes a channel.

**Usage:** `!delete-channel <channel-id|channel-name>`

**Example:** `!delete-channel 123456789012345678` or `!delete-channel #general`

### !edit-channel-autohide
**Description:** Edits channel autohide settings.

**Usage:** `!edit-channel-autohide <channel-id> <minutes [60, 1440, 4320, 10080]>`

**Example:** `!edit-channel-autohide 123456789012345678 1440`

### !edit-channel-name
**Description:** Edits a channel name.

**Usage:** `!edit-channel-name <channel-id> <new-name>`

**Example:** `!edit-channel-name 123456789012345678 new-channel-name`

### !edit-channel-nsfw
**Description:** Edits a channel age-rating or "not suitable for work" NSFW.

**Usage:** `!edit-channel-nsfw <channel-id> <true|false>`

**Example:** `!edit-channel-nsfw 123456789012345678 true`

### !edit-channel-slowmode
**Description:** Edits a channel to have slowmode.

**Usage:** `!edit-channel-slowmode <channel-id> <seconds [0 - 21600]>`

**Example:** `!edit-channel-slowmode 123456789012345678 10`

### !edit-channel-topic
**Description:** Edits a channel topic.

**Usage:** `!edit-channel-topic <channel-id> <new-topic>`

**Example:** `!edit-channel-topic 123456789012345678 New topic description`

### !lock-channel
**Description:** Locks or unlocks a text channel.

**Usage:** `!lock-channel <channel-id> <true|false>`

**Example:** `!lock-channel 123456789012345678 true`

### !new-channel
**Description:** Creates a new text or voice channel.

**Usage:** `!new-channel <channel-name> <channel-type> [category-id] [channel-topic]`

**Example:** `!new-channel test-channel text 123456789012345678 "A fun chat for everyone!"`

### !set-inactive
**Description:** Sets a timeout for marking a channel as inactive.

**Usage:** `!set-inactive <channel-name|channel-id> <timeout>`

**Example:** `!set-inactive general-voice 300`

### !vanish
**Description:** Hides a text channel for everyone but admins.

**Usage:** `!vanish <channel>`

**Example:** `!vanish #general`

### !unvanish
**Description:** Restores visibility to a previously vanished channel for everyone.

**Usage:** `!unvanish <channel>`

**Example:** `!unvanish #general`

---

## Category Management

### !delete-category
**Description:** Deletes a category.

**Usage:** `!delete-category <category-id>`

**Example:** `!delete-category 123456789012345678`

### !new-category
**Description:** Creates a new category in the server.

**Usage:** `!new-category <category-name>`

**Example:** `!new-category test-category`

---

## Role Management

### !assign-role
**Description:** Assigns a role to one or more users.

**Usage:** `!assign-role <role-name> <@user1> <@user2> ...`

**Example:** `!assign-role VIP 987654321098765432`

### !delete-role
**Description:** Deletes a role.

**Usage:** `!delete-role <role-name>`

**Example:** `!delete-role VIP`

### !new-role
**Description:** Creates a new role with optional color and hoist settings.

**Usage:** `!new-role <role-name> [color] [hoist]`

**Example:** `!new-role VIP #3498db yes`

### !remove-role
**Description:** Removes a role from one or more users.

**Usage:** `!remove-role <role-name> <@user1> <@user2> ...`

**Example:** `!remove-role VIP 123456789012345678 123456789012345678`

---

## User Management

### !ban
**Description:** Bans a user from the server.

**Usage:** `!ban <user-id>`

**Example:** `!ban 123456789012345678`

### !disconnect
**Description:** Disconnects one or more users from a voice channel.

**Usage:** `!disconnect <@user1> [@user2] ...`

**Example:** `!disconnect @User1 @User2`

### !kick
**Description:** Kicks a user from the server.

**Usage:** `!kick <user-id>`

**Example:** `!kick 123456789012345678`

### !move-user
**Description:** Moves a user to a different voice channel.

**Usage:** `!move-user <@userID | userID> <channelID>`

**Example:** `!move-user 123456789012345678 123456789012345678`

### !mute
**Description:** Mutes a user in the server.

**Usage:** `!mute <user-id>`

**Example:** `!mute 123456789012345678`

### !prune
**Description:** Kicks members inactive for the specified number of days.

**Usage:** `!prune <days>`

**Example:** `!prune 30`

### !set-nickname
**Description:** Changes a user's nickname in the server.

**Usage:** `!set-nickname <@user> <nickname>`

**Example:** `!set-nickname @JohnDoe CoolGuy123`

### !unban
**Description:** Unbans a user from the server.

**Usage:** `!unban <user-id>`

**Example:** `!unban 1335401202648748064`

### !unmute
**Description:** Unmutes a user in the server.

**Usage:** `!unmute <user-id>`

**Example:** `!unmute 123456789012345678`

---

## Voice Channel Management

### !lock-voice
**Description:** Locks or unlocks a voice channel.

**Usage:** `!lock-voice <channel-id> <true|false>`

**Example:** `!lock-voice 123456789012345678 true`

---

## Message Management

### !notify
**Description:** Sends an embedded announcement to a specified channel with mentions.

**Usage:** `!notify <#channel> <(@user, @everyone, @here, @role)> [title] | <message>`

**Example:** `!notify #announcements @everyone Server Maintenance | The server will be down at midnight.`

### !pin
**Description:** Pins a specific message or the last message in the channel.

**Usage:** `!pin <message-id>` or `!pin this`

**Example:** `!pin 123456789012345678` or `!pin this`

### !poll
**Description:** Creates a poll with multiple voting options.

**Usage:** `!poll "Question" "Option 1" "Option 2" "Option 3"`

**Example:** `!poll "What should we play?" "Minecraft" "Valorant" "Overwatch"`

### !purge
**Description:** Deletes a specified number of messages from a channel.

**Usage:** `!purge <#channel|channel-id|this> <number|all>`

**Example:** `!purge this all` or `!purge #general 100`

### !scheduled-message
**Description:** Schedules a message to be sent at a later time in a specific channel.

**Usage:** `!scheduled-message <#channel> <YYYY-MM-DD HH:MM> <message>`

**Example:** `!scheduled-message #announcements 2025-02-07 18:48 Server maintenance Starting!`

### !unpin
**Description:** Unpins a specified message.

**Usage:** `!unpin <message-id> | oldest | latest`

**Example:** `!unpin 123456789012345678`

---

## Event Management

### !create-event
**Description:** Creates a new scheduled event.

**Usage:** `!create-event <event-topic> | <start-date> | <start-time> | <event-frequency> | <location> | <description> | [cover-image-url]`

**Example:** `!create-event "Weekly Meeting" | 2025-02-10 | 14:00 | weekly | #general | "Join us for our weekly team meeting" | https://example.com/cover.jpg`

### !delete-event
**Description:** Deletes a scheduled event.

**Usage:** `!delete-event <event-id>`

**Example:** `!delete-event 123456789012345678`

---

## Server Management

### !display-boost
**Description:** Displays Nitro boost bar status.

**Usage:** `!display-boost <true|false>`

**Example:** `!display-boost true`

---

## Utility Commands

### !color
**Description:** Displays a list of 50 hex colors with their corresponding names.

**Usage:** `!color list | <color-name>`

**Example:** `!color list` or `!color red`

### !help
**Description:** Displays a list of available commands.

**Usage:** `!help`

**Example:** `!help`

---

## Command Categories Summary

| Category | Commands |
|----------|----------|
| **AI Assistant** | neon |
| **Channel Management** | assign-channel, delete-channel, edit-channel-autohide, edit-channel-name, edit-channel-nsfw, edit-channel-slowmode, edit-channel-topic, lock-channel, new-channel, set-inactive, vanish, unvanish |
| **Category Management** | delete-category, new-category |
| **Role Management** | assign-role, delete-role, new-role, remove-role |
| **User Management** | ban, disconnect, kick, move-user, mute, prune, set-nickname, unban, unmute |
| **Voice Channel Management** | lock-voice |
| **Message Management** | notify, pin, poll, purge, scheduled-message, unpin |
| **Event Management** | create-event, delete-event |
| **Server Management** | display-boost |
| **Utility Commands** | color, help |

---

## Notes

- **Channel IDs and User IDs**: Most commands use Discord's unique snowflake IDs. You can obtain these by enabling Developer Mode in Discord settings and right-clicking on channels/users.
- **Permissions**: Many commands require appropriate Discord permissions. Ensure the bot has the necessary permissions for the actions you want to perform.
- **Time Formats**: For scheduled commands, use 24-hour format (HH:MM) and ISO date format (YYYY-MM-DD).
- **Boolean Values**: Use `true` or `false` for boolean parameters.
- **Optional Parameters**: Parameters in square brackets `[parameter]` are optional.
- **Multiple Users**: Some commands accept multiple user mentions or IDs separated by spaces.

---

## Getting Help

For additional help or troubleshooting:
1. Use `!help` to see available commands
2. Use `!neon <your question>` to ask the AI assistant for help with server management
3. Check the bot's permissions if commands aren't working
4. Ensure you have the necessary Discord permissions to execute administrative commands

---

*This documentation is automatically generated from the bot's command database. Commands marked as inactive in the database are not included in this list.*
