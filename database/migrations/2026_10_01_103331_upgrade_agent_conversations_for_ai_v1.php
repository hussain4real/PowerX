<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Migrations\AiMigration;

return new class extends AiMigration
{
    public function up(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);
        $conversations = config('ai.conversations.tables.conversations', 'agent_conversations');
        $messages = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        foreach ([$conversations, $messages] as $tableName) {
            $schema->table($tableName, function (Blueprint $table): void {
                $table->string('participant_type')->nullable();
                $table->unsignedBigInteger('participant_id')->nullable();
                $table->index(['participant_type', 'participant_id', 'updated_at']);
            });

            DB::connection($connection)->table($tableName)->whereNotNull('user_id')->update([
                'participant_type' => (new User)->getMorphClass(),
                'participant_id' => DB::raw('user_id'),
            ]);
        }

        $schema->table($messages, function (Blueprint $table): void {
            $table->longText('steps')->nullable();
            $table->string('status', 25)->default('completed');
            $table->text('tool_calls')->nullable()->change();
            $table->text('tool_results')->nullable()->change();
        });

        DB::connection($connection)->table($messages)->orderBy('id')->chunkById(100, function ($rows) use ($connection, $messages): void {
            foreach ($rows as $row) {
                $calls = json_decode($row->tool_calls, true, 512, JSON_THROW_ON_ERROR);
                $results = collect(json_decode($row->tool_results, true, 512, JSON_THROW_ON_ERROR))->keyBy('id');
                $calls = array_map(function (array $call) use ($results): array {
                    $result = $results->get($call['id']);

                    return $result === null ? $call : array_merge($call, $result);
                }, $calls);

                DB::connection($connection)->table($messages)->where('id', $row->id)->update([
                    'steps' => json_encode($row->role === 'assistant' ? [[
                        'content' => $row->content,
                        'tool_calls' => $calls,
                        'reasoning' => '',
                        'replay_blocks' => [],
                        'provider_tool_calls' => [],
                    ]] : [], JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    public function down(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);
        $conversations = config('ai.conversations.tables.conversations', 'agent_conversations');
        $messages = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        foreach ([$conversations, $messages] as $tableName) {
            DB::connection($connection)->table($tableName)
                ->where('participant_type', (new User)->getMorphClass())
                ->whereNull('user_id')
                ->update(['user_id' => DB::raw('participant_id')]);
        }

        DB::connection($connection)->table($messages)->whereNull('tool_calls')->orderBy('id')->chunkById(100, function ($rows) use ($connection, $messages): void {
            foreach ($rows as $row) {
                $steps = collect(json_decode($row->steps, true, 512, JSON_THROW_ON_ERROR));
                $calls = $steps->flatMap(fn (array $step): array => $step['tool_calls'] ?? [])->values();
                $results = $calls->filter(fn (array $call): bool => array_key_exists('result', $call))->values();

                DB::connection($connection)->table($messages)->where('id', $row->id)->update([
                    'tool_calls' => $calls->map(fn (array $call): array => array_diff_key($call, array_flip(['result', 'denied', 'failed'])))->toJson(),
                    'tool_results' => $results->toJson(),
                ]);
            }
        });

        foreach (['tool_calls', 'tool_results'] as $column) {
            DB::connection($connection)->table($messages)->whereNull($column)->update([$column => '[]']);
        }

        $schema->table($messages, function (Blueprint $table): void {
            $table->dropColumn(['steps', 'status']);
            $table->text('tool_calls')->nullable(false)->change();
            $table->text('tool_results')->nullable(false)->change();
        });

        foreach ([$conversations, $messages] as $tableName) {
            $schema->table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['participant_type', 'participant_id', 'updated_at']);
                $table->dropColumn(['participant_type', 'participant_id']);
            });
        }
    }
};
