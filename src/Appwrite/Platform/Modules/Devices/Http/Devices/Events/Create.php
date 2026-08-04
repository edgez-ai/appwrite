<?php

namespace Appwrite\Platform\Modules\Devices\Http\Devices\Events;

use Appwrite\Devices\Mqtt;
use Appwrite\Devices\Presence;
use Appwrite\Event\Event;
use Appwrite\Event\Message\Func as FunctionMessage;
use Appwrite\Event\Publisher\Func as FunctionPublisher;
use Appwrite\Event\Webhook;
use Appwrite\Extend\Exception;
use Appwrite\Functions\EventProcessor;
use Appwrite\Utopia\Request;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\System\System;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createDeviceEvent';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/devices/events')
            ->desc('Receive an EMQX webhook event')
            ->groups(['api', 'devices', 'internal'])
            ->label('scope', 'public')
            ->label('resourceType', RESOURCE_TYPE_DEVICES)
            ->inject('request')
            ->inject('response')
            ->inject('dbForPlatform')
            ->inject('getProjectDB')
            ->inject('authorization')
            ->inject('publisherForFunctions')
            ->inject('queueForEvents')
            ->inject('queueForWebhooks')
            ->inject('eventProcessor')
            ->callback($this->action(...));
    }

    public function action(
        Request $request,
        Response $response,
        Database $dbForPlatform,
        callable $getProjectDB,
        Authorization $authorization,
        FunctionPublisher $publisherForFunctions,
        Event $queueForEvents,
        Webhook $queueForWebhooks,
        EventProcessor $eventProcessor,
    ): void {
        $this->verifySecret($request);

        $rawPayload = $request->getRawPayload();
        try {
            $payload = \json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new Exception(Exception::GENERAL_ARGUMENT_INVALID, 'EMQX webhook payload must be valid JSON.');
        }

        if (!\is_array($payload)) {
            throw new Exception(Exception::GENERAL_ARGUMENT_INVALID, 'EMQX webhook payload must be a JSON object.');
        }

        $event = $payload['event'] ?? '';
        $eventParts = \is_string($event) ? Mqtt::getAppwriteEventParts($event) : null;
        if ($eventParts === null) {
            throw new Exception(Exception::GENERAL_ARGUMENT_INVALID, 'Unsupported EMQX webhook event.');
        }

        $route = $this->resolveDeviceRoute($payload, $dbForPlatform, $authorization);
        if ($route->isEmpty()) {
            $response
                ->setStatusCode(Response::STATUS_CODE_ACCEPTED)
                ->json([
                    'accepted' => false,
                    'event' => $event,
                    'reason' => 'device_not_found',
                ]);
            return;
        }

        $projectId = $route->getAttribute('projectId', '');
        $project = $authorization->skip(fn () => $dbForPlatform->getDocument('projects', $projectId));
        if ($project->isEmpty()) {
            throw new Exception(Exception::PROJECT_NOT_FOUND);
        }

        $dbForProject = $getProjectDB($project);
        $deviceId = $route->getAttribute('deviceId', '');
        $device = $authorization->skip(fn () => $dbForProject->getDocument('devices', $deviceId));
        if ($device->isEmpty()) {
            $response
                ->setStatusCode(Response::STATUS_CODE_ACCEPTED)
                ->json([
                    'accepted' => false,
                    'event' => $event,
                    'reason' => 'device_not_found',
                ]);
            return;
        }

        $presenceChanges = Presence::getChanges($event, $payload, $device);
        if (!empty($presenceChanges)) {
            $authorization->skip(fn () => $dbForProject->updateDocument(
                'devices',
                $deviceId,
                new Document($presenceChanges),
            ));
        }

        $mqttCategory = $eventParts['category'];
        $mqttAction = $eventParts['action'];
        $appwriteEvent = 'devices.[deviceId].mqtt.[mqttCategory].' . $mqttAction;
        $queueForEvents
            ->setProject($project)
            ->setEvent($appwriteEvent)
            ->setParam('deviceId', $deviceId)
            ->setParam('mqttCategory', $mqttCategory)
            ->setPayload($payload);

        $generatedEvents = Event::generateEvents($appwriteEvent, $queueForEvents->getParams());
        $functionQueued = false;
        $functionEvents = $eventProcessor->getFunctionsEvents($project, $dbForProject);
        if (!empty($functionEvents)) {
            foreach ($generatedEvents as $generatedEvent) {
                if (!isset($functionEvents[$generatedEvent])) {
                    continue;
                }

                $publisherForFunctions->enqueue(FunctionMessage::fromEvent(
                    event: $appwriteEvent,
                    params: $queueForEvents->getParams(),
                    project: $project,
                    payload: $payload,
                ));
                $functionQueued = true;
                break;
            }
        }

        $webhookQueued = false;
        $webhookEvents = $eventProcessor->getWebhooksEvents($project);
        if (!empty($webhookEvents)) {
            foreach ($generatedEvents as $generatedEvent) {
                if (!isset($webhookEvents[$generatedEvent])) {
                    continue;
                }

                $queueForWebhooks
                    ->from($queueForEvents)
                    ->trigger();
                $webhookQueued = true;
                break;
            }
        }

        // This request resolves its project from the device route, after the regular
        // request project has been initialized. Dispatch is handled above using the
        // resolved project, so prevent the generic shutdown hook from dispatching it again.
        $queueForEvents->reset();

        $response
            ->setStatusCode(Response::STATUS_CODE_ACCEPTED)
            ->json([
                'accepted' => true,
                'event' => $event,
                'functionQueued' => $functionQueued,
                'webhookQueued' => $webhookQueued,
            ]);
    }

    private function verifySecret(Request $request): void
    {
        $expectedSecret = System::getEnv('_APP_DEVICES_EMQX_SECRET', '');
        $providedSecret = $request->getHeaderLine('x-appwrite-emqx-secret', '');

        if (empty($expectedSecret) || empty($providedSecret) || !\hash_equals($expectedSecret, $providedSecret)) {
            throw new Exception(Exception::GENERAL_ACCESS_FORBIDDEN, 'Invalid EMQX webhook secret.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveDeviceRoute(
        array $payload,
        Database $dbForPlatform,
        Authorization $authorization,
    ): Document {
        $clientAttributes = \is_array($payload['client_attrs'] ?? null) ? $payload['client_attrs'] : [];
        $candidates = [
            $clientAttributes['deviceId'] ?? '',
            $payload['from_clientid'] ?? '',
            $payload['clientid'] ?? '',
        ];
        $seen = [];
        foreach ($candidates as $clientId) {
            if (!\is_string($clientId) || $clientId === '' || isset($seen[$clientId])) {
                continue;
            }
            $seen[$clientId] = true;

            $route = $authorization->skip(fn () => $dbForPlatform->getDocument('deviceRoutes', $clientId));
            if (!$route->isEmpty() && $route->getAttribute('deviceId') === $clientId) {
                return $route;
            }
        }

        return new Document();
    }
}
