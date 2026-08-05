# Agent-native project management

This module adds a lightweight coordination layer for project-management and development agents. It deliberately uses the existing Appwrite project as the tenant boundary. There is no second organization, project, user, team, or API-key system.

## Architecture

Chat adapters for Slack, Microsoft Teams, and WeCom resolve a `chatBinding`, then call Appwrite with a project API key. The project-management agent turns conversation into Tasks and immutable Stickers. A development agent reads a deterministic task context, records an Agent Run, and attaches Evidence. Ordinary Appwrite events allow Functions, webhooks, and Realtime consumers to orchestrate work without coupling this module to a specific model or chat SDK.

```text
Slack / Teams / WeCom
        |
        v
chat binding -> PM agent -> Tasks + Stickers
                              |
                              v
                       task context snapshot
                              |
                              v
                     development agent -> Evidence
```

## Data model

- `projectTasks`: hierarchical work items with workflow status, priority, assignment, acceptance criteria, and document permissions.
- `stickers`: append-only knowledge. Content and provenance are immutable; only validity metadata can change through the status endpoint.
- `stickerGroups`: contextual groups whose summary becomes stale when related knowledge changes.
- `stickerGroupSummaries`: immutable, versioned summary records. Generation is intentionally left to an Appwrite Function or external agent.
- `agentRuns`: execution state plus the exact input-context snapshot used by an agent.
- `evidence`: immutable links to commits, pull requests, tests, deployments, Storage files, or other artifacts.
- `activityEvents`: append-only audit-oriented domain activity.
- `chatBindings`: unique mappings from provider tenant/conversation/thread to the Appwrite project. The unique key is SHA-256 hashed and is never returned.

All collections are stored in the project database. Appwrite project API keys therefore see only that project's records. Session and JWT callers are additionally constrained by document permissions, including existing Appwrite user and team roles.

## API surface

| Method | Route | Purpose |
| --- | --- | --- |
| `POST/GET/PATCH/DELETE` | `/v1/tasks[/:taskId]` | Task lifecycle |
| `PATCH` | `/v1/tasks/:taskId/claim` | Atomically claim a ready task |
| `PATCH` | `/v1/tasks/:taskId/status` | Compare-and-set workflow transition |
| `GET` | `/v1/tasks/:taskId/context` | Deterministic execution context |
| `GET` | `/v1/project/context` | Project-agent context |
| `GET` | `/v1/project-management/capabilities` | Machine-readable agent contract |
| `POST/GET` | `/v1/stickers[/:stickerId]` | Create/list/read immutable knowledge |
| `PATCH` | `/v1/stickers/:stickerId/status` | Validate, invalidate, or supersede a Sticker |
| `POST/GET` | `/v1/sticker-groups` | Create or query context groups |
| `POST` | `/v1/sticker-groups/:groupId/summaries` | Store a versioned generated summary |
| `POST/GET/PATCH` | `/v1/agent-runs[/:runId]` | Queue, query, and transition agent execution |
| `POST/GET` | `/v1/evidence` | Attach or query immutable execution evidence |
| `POST/GET/DELETE` | `/v1/chat-bindings[/:bindingId]` | Manage chat routing |

The API-key scopes are `projectManagement.read` and `projectManagement.write`. Production deployments should create separate keys per agent and adapter, grant only the necessary scope, and rotate the keys independently.

## Agent-friendly contract

Agents should begin by reading `/v1/project-management/capabilities`. It describes enum values, legal state transitions, context endpoints, retry rules, and high-level actions without requiring the model to infer them from prose.

Create requests accept caller-defined resource IDs. Generate the ID once and reuse it across retries. Replaying the same ID with the same semantic input returns the existing resource with HTTP 200 and does not emit another event. Reusing the ID with different input returns `project_resource_already_exists` with HTTP 409.

Agents should transition Tasks using `/v1/tasks/:taskId/status` with both `expectedStatus` and `status`. A stale observation returns `project_task_status_conflict` with HTTP 409, allowing the agent to reload context instead of overwriting another actor. Development agents should use the claim endpoint to move a Task from `ready` to `in_progress`; the database row is locked during the operation, and repeating the claim for the same agent is idempotent.

Agent Run updates follow the same compare-and-set rule with `expectedStatus`. Context responses include `schemaVersion`, `contextType`, legal next states, child and parent Tasks, direct valid Stickers, current group summaries, constraints, decisions, previous runs, and Evidence.

## Events and orchestration

Tasks, Stickers, Sticker Groups, Agent Runs, Evidence, and Chat Bindings use the regular Appwrite event pipeline. They can be selected in Function event triggers and project webhooks. Important examples include:

- `tasks.*.create`, `tasks.*.update`, `tasks.*.delete`
- `stickers.*.create`, `stickers.*.update.status`
- `agentRuns.*.create`, `agentRuns.*.update`
- `evidence.*.create`
- `chatBindings.*.create`, `chatBindings.*.delete`

A practical orchestration function watches for a Task entering `ready`, claims it by updating status, creates an Agent Run, obtains `/v1/tasks/:taskId/context`, and submits work to the development agent. Completion records Evidence and updates the run and task. Idempotency belongs at this orchestration boundary; use the Appwrite event ID or a stable external message ID.

## Upgrade

Migration `V27` creates the project collections for existing tenants. Fresh projects obtain the same collections from the collection configuration. Run the normal Appwrite migration command when deploying the image that contains this module.
