<?php

use App\Ai\Agents\PowerXCourseGuide;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Storage\DatabaseConversationStore;

test('AI v1 conversation upgrade preserves legacy ownership and tool transcripts', function (): void {
    $migration = require database_path('migrations/2026_10_01_103331_upgrade_agent_conversations_for_ai_v1.php');
    $migration->down();
    $user = User::factory()->create();
    $conversationId = (string) Str::uuid();
    $messageId = (string) Str::uuid();
    $call = ['id' => 'call-1', 'name' => 'catalog', 'arguments' => ['query' => 'course']];
    $result = [...$call, 'result' => 'Approved course', 'result_id' => 'result-1'];
    DB::table('agent_conversations')->insert([
        'id' => $conversationId, 'user_id' => $user->id, 'title' => 'Legacy conversation',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('agent_conversation_messages')->insert([
        'id' => $messageId, 'conversation_id' => $conversationId, 'user_id' => $user->id,
        'agent' => PowerXCourseGuide::class, 'role' => 'assistant', 'content' => 'Here is a course.',
        'attachments' => '[]', 'tool_calls' => json_encode([$call]), 'tool_results' => json_encode([$result]),
        'usage' => '{}', 'meta' => '{}', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $migration->up();
    $store = new DatabaseConversationStore;
    expect($store->conversationBelongsTo($conversationId, $user->getMorphClass(), $user->id))->toBeTrue()
        ->and($store->conversationBelongsTo($conversationId, $user->getMorphClass(), $user->id + 1))->toBeFalse();
    $transcript = $store->getLatestConversationMessages($conversationId, 10);
    expect($transcript)->toHaveCount(2)
        ->and($transcript[0]->content)->toBe('Here is a course.')
        ->and($transcript[1]->toolResults->first()->result)->toBe('Approved course');
    $row = DB::table('agent_conversation_messages')->where('id', $messageId)->first();
    expect(json_decode($row->tool_calls, true))->toBe([$call])
        ->and(json_decode($row->tool_results, true))->toBe([$result])
        ->and($row->status)->toBe('completed');

    $newConversation = $store->storeConversation($user->getMorphClass(), $user->id, 'New conversation');
    $newMessage = $store->storeUserMessage($newConversation, $user->getMorphClass(), $user->id, PowerXCourseGuide::class, new UserMessage('Hello'));
    expect($store->getLatestConversationMessages($newConversation, 10)->first()->content)->toBe('Hello');

    $migration->down();
    expect(Schema::hasColumn('agent_conversations', 'participant_type'))->toBeFalse()
        ->and(DB::table('agent_conversation_messages')->where('id', $messageId)->value('tool_results'))->toBe(json_encode([$result]))
        ->and(DB::table('agent_conversation_messages')->where('id', $newMessage)->value('tool_calls'))->toBe('[]')
        ->and(DB::table('agent_conversations')->where('id', $newConversation)->value('user_id'))->toBe($user->id);
    $migration->up();
});
