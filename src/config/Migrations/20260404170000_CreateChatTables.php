<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateChatTables Migration
 *
 * Creates chat_conversations and chat_messages tables for the AI Chat Assistant feature.
 */
class CreateChatTables extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // chat_conversations table
        $this->table('chat_conversations')
            ->addColumn('public_id', 'string', [
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('title', 'string', [
                'limit' => 255,
                'default' => 'New conversation',
                'null' => false,
            ])
            ->addColumn('message_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('input_tokens_used', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('output_tokens_used', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'active',
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['public_id'], ['unique' => true, 'name' => 'idx_chat_conversations_public_id'])
            ->addIndex(['organization_id'], ['name' => 'idx_chat_conversations_org_id'])
            ->addIndex(['user_id'], ['name' => 'idx_chat_conversations_user_id'])
            ->addIndex(['status'], ['name' => 'idx_chat_conversations_status'])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_chat_conversations_organization_id',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_chat_conversations_user_id',
            ])
            ->create();

        // chat_messages table
        $this->table('chat_messages')
            ->addColumn('conversation_id', 'integer', [
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('role', 'string', [
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('content', 'text', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('tool_calls', 'text', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('tool_results', 'text', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('input_tokens', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('output_tokens', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['conversation_id'], ['name' => 'idx_chat_messages_conversation_id'])
            ->addIndex(['role'], ['name' => 'idx_chat_messages_role'])
            ->addForeignKey('conversation_id', 'chat_conversations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_chat_messages_conversation_id',
            ])
            ->create();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('chat_messages')->drop()->save();
        $this->table('chat_conversations')->drop()->save();
    }
}
