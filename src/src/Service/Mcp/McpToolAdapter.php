<?php
declare(strict_types=1);

namespace App\Service\Mcp;

use App\Service\Assistant\ToolExecutor;
use App\Service\Assistant\ToolRegistry;
use Cake\Log\LogTrait;

/**
 * McpToolAdapter
 *
 * Converts ToolRegistry definitions to MCP tool format and wraps
 * ToolExecutor to return results in MCP content format.
 */
class McpToolAdapter
{
    use LogTrait;

    /**
     * @var \App\Service\Assistant\ToolRegistry
     */
    private ToolRegistry $toolRegistry;

    /**
     * @var \App\Service\Assistant\ToolExecutor
     */
    private ToolExecutor $toolExecutor;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->toolRegistry = new ToolRegistry();
        $this->toolExecutor = new ToolExecutor();
    }

    /**
     * Get tools list in MCP format for the given role.
     *
     * Maps ToolRegistry's `input_schema` key to MCP's `inputSchema` key
     * and returns tools as an array of {name, description, inputSchema}.
     *
     * @param string $role The user's role (maps API key permissions to role).
     * @return array<array> Tools in MCP format.
     */
    public function getToolsList(string $role): array
    {
        $tools = $this->toolRegistry->getToolsForRole($role);

        return array_map(function (array $tool): array {
            return [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'inputSchema' => $tool['input_schema'],
            ];
        }, $tools);
    }

    /**
     * Execute a tool and return the result in MCP content format.
     *
     * Wraps ToolExecutor::execute() and formats the result as an array
     * of MCP content blocks: [{type: "text", text: "...JSON..."}].
     *
     * @param string $name The tool name.
     * @param array $arguments The tool arguments.
     * @param int $orgId The organization ID.
     * @param int $userId The user ID.
     * @param string $role The user's role.
     * @return array{content: array, isError?: bool} MCP tool result.
     */
    public function executeTool(string $name, array $arguments, int $orgId, int $userId, string $role): array
    {
        // Verify the tool exists for this role
        $availableNames = $this->toolRegistry->getToolNamesForRole($role);
        if (!in_array($name, $availableNames, true)) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode(['error' => "Tool '{$name}' not found or not available for your permission level."]),
                    ],
                ],
                'isError' => true,
            ];
        }

        try {
            $result = $this->toolExecutor->execute($name, $arguments, $orgId, $userId, $role);

            $isError = isset($result['error']);

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'isError' => $isError,
            ];
        } catch (\Throwable $e) {
            $this->log("MCP tool execution error for {$name}: {$e->getMessage()}", 'error');

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode(['error' => "Tool execution failed: {$e->getMessage()}"]),
                    ],
                ],
                'isError' => true,
            ];
        }
    }

    /**
     * Map API key permissions to an internal role string.
     *
     * The ToolRegistry uses 'owner'/'admin' as write roles. This method
     * translates API key permission levels to those roles.
     *
     * @param array<string> $permissions API key permissions array.
     * @return string The mapped role.
     */
    public static function permissionsToRole(array $permissions): string
    {
        if (in_array('admin', $permissions, true)) {
            return 'admin';
        }

        if (in_array('write', $permissions, true)) {
            return 'admin';
        }

        return 'viewer';
    }
}
