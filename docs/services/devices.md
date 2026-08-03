# Devices

The Devices service registers MQTT devices inside an Appwrite project. Project API keys use the `devices.read` and `devices.write` scopes. Authenticated users and teams receive per-device access through standard Appwrite permissions.

Device credentials are separate from Appwrite API keys. Appwrite generates the device `$id`, while the customer supplies a serial number that is unique within the project. Creating or rotating credentials returns an MQTT password once; Appwrite stores only its Argon2 hash. The MQTT username is the device serial number and the MQTT client ID is the generated device `$id`.

EMQX calls the internal device-session endpoint to validate the credential. A successful response contains ACL rules that allow the device to publish its own telemetry and events and subscribe to its own commands.
