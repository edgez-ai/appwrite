<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Services;

use Appwrite\Platform\Modules\ProjectManagement\Http\AgentRuns\Create as CreateAgentRun;
use Appwrite\Platform\Modules\ProjectManagement\Http\AgentRuns\Update as UpdateAgentRun;
use Appwrite\Platform\Modules\ProjectManagement\Http\AgentRuns\XList as ListAgentRuns;
use Appwrite\Platform\Modules\ProjectManagement\Http\ChatBindings\Create as CreateChatBinding;
use Appwrite\Platform\Modules\ProjectManagement\Http\ChatBindings\Delete as DeleteChatBinding;
use Appwrite\Platform\Modules\ProjectManagement\Http\ChatBindings\XList as ListChatBindings;
use Appwrite\Platform\Modules\ProjectManagement\Http\Evidence\Create as CreateEvidence;
use Appwrite\Platform\Modules\ProjectManagement\Http\Evidence\XList as ListEvidence;
use Appwrite\Platform\Modules\ProjectManagement\Http\Project\Context\Get as GetProjectContext;
use Appwrite\Platform\Modules\ProjectManagement\Http\ProjectManagement\Capabilities\Get as GetCapabilities;
use Appwrite\Platform\Modules\ProjectManagement\Http\StickerGroups\Create as CreateStickerGroup;
use Appwrite\Platform\Modules\ProjectManagement\Http\StickerGroups\Summaries\Create as CreateStickerGroupSummary;
use Appwrite\Platform\Modules\ProjectManagement\Http\StickerGroups\XList as ListStickerGroups;
use Appwrite\Platform\Modules\ProjectManagement\Http\Stickers\Create as CreateSticker;
use Appwrite\Platform\Modules\ProjectManagement\Http\Stickers\Get as GetSticker;
use Appwrite\Platform\Modules\ProjectManagement\Http\Stickers\Status\Update as UpdateStickerStatus;
use Appwrite\Platform\Modules\ProjectManagement\Http\Stickers\XList as ListStickers;
use Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Claim\Update as ClaimTask;
use Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Context\Get as GetTaskContext;
use Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Create as CreateTask;
use Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Delete as DeleteTask;
use Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Get as GetTask;
use Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Status\Update as UpdateTaskStatus;
use Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Update as UpdateTask;
use Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\XList as ListTasks;
use Utopia\Platform\Service;

class Http extends Service
{
    public function __construct()
    {
        $this->type = Service::TYPE_HTTP;
        $this->addAction(CreateTask::getName(), new CreateTask());
        $this->addAction(GetTask::getName(), new GetTask());
        $this->addAction(ListTasks::getName(), new ListTasks());
        $this->addAction(UpdateTask::getName(), new UpdateTask());
        $this->addAction(DeleteTask::getName(), new DeleteTask());
        $this->addAction(GetTaskContext::getName(), new GetTaskContext());
        $this->addAction(ClaimTask::getName(), new ClaimTask());
        $this->addAction(UpdateTaskStatus::getName(), new UpdateTaskStatus());
        $this->addAction(CreateSticker::getName(), new CreateSticker());
        $this->addAction(GetSticker::getName(), new GetSticker());
        $this->addAction(ListStickers::getName(), new ListStickers());
        $this->addAction(UpdateStickerStatus::getName(), new UpdateStickerStatus());
        $this->addAction(CreateStickerGroup::getName(), new CreateStickerGroup());
        $this->addAction(ListStickerGroups::getName(), new ListStickerGroups());
        $this->addAction(CreateStickerGroupSummary::getName(), new CreateStickerGroupSummary());
        $this->addAction(CreateChatBinding::getName(), new CreateChatBinding());
        $this->addAction(ListChatBindings::getName(), new ListChatBindings());
        $this->addAction(DeleteChatBinding::getName(), new DeleteChatBinding());
        $this->addAction(GetProjectContext::getName(), new GetProjectContext());
        $this->addAction(GetCapabilities::getName(), new GetCapabilities());
        $this->addAction(CreateAgentRun::getName(), new CreateAgentRun());
        $this->addAction(ListAgentRuns::getName(), new ListAgentRuns());
        $this->addAction(UpdateAgentRun::getName(), new UpdateAgentRun());
        $this->addAction(CreateEvidence::getName(), new CreateEvidence());
        $this->addAction(ListEvidence::getName(), new ListEvidence());
    }
}
