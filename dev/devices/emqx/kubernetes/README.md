# EMQX 5.8 for Appwrite Devices

This example bootstraps a three-core-node EMQX 5.8 Open Source cluster with EMQX Operator 2.2.29. One broker cluster serves every Appwrite project. Appwrite authenticates each MQTT connection and returns a project-and-device-specific ACL. The image is pinned to the multi-platform EMQX 5.8.9 manifest digest.

## Build the Appwrite image

The existing `Release` GitHub Actions workflow builds the production target for `linux/amd64` and `linux/arm64`, then publishes:

```text
DOCKERHUB_USERNAME/appwrite:<release-or-manual-tag>
DOCKERHUB_USERNAME/appwrite:latest
```

Configure these GitHub repository secrets before running it:

- `DOCKERHUB_USERNAME`: Docker Hub account or organization name.
- `DOCKERHUB_TOKEN`: Docker Hub access token with permission to push the `appwrite` repository.

Publish a GitHub release or run the `Release` workflow manually with a tag such as `devices-2026-08-03`. Configure the Appwrite Kubernetes workload to use that immutable tag:

```yaml
image: DOCKERHUB_USERNAME/appwrite:devices-2026-08-03
imagePullPolicy: IfNotPresent
```

If the Docker Hub repository is private, add an `imagePullSecret` to the Appwrite workload's namespace. Make sure every Appwrite API replica also receives `_APP_DEVICES_EMQX_SECRET` from a Kubernetes Secret.

## Install the compatible operator

EMQX 5.8 requires the 2.2.x operator and its `apps.emqx.io/v2beta1` API. Do not install Operator 2.3.x for this deployment.

```sh
helm repo add emqx https://repos.emqx.io/charts
helm repo update
helm upgrade --install emqx-operator emqx/emqx-operator \
  --namespace emqx-operator-system \
  --create-namespace \
  --version 2.2.29
```

## Configure authentication

1. Generate a cryptographically random shared secret.
2. Set the same value as `_APP_DEVICES_EMQX_SECRET` on every Appwrite API replica.
3. Copy `authentication-secret.example.yaml` outside the repository, replace `REPLACE_WITH_THE_APPWRITE_EMQX_SECRET`, and verify the `appwrite` Service URL.
4. Apply the rendered Secret, then apply `emqx.yaml`.

Do not commit the rendered Secret.

```sh
kubectl apply -f /secure/path/authentication-secret.yaml
kubectl apply -f dev/devices/emqx/kubernetes/emqx.yaml
kubectl get emqx appwrite-emqx -n appwrite
```

The device connects with values returned by `POST /v1/devices/{deviceId}/credentials`:

```text
username = device.serial
clientId = device.$id
password = generated one-time secret
```

EMQX sends the client ID to Appwrite's internal authentication endpoint. Appwrite resolves the owning project through its private device route index before checking the serial, password, enabled state, expiry, and project-specific ACL.

The example keeps the Operator-generated listener Service internal as a `ClusterIP`. Before production use, configure the EMQX TLS listener with a Kubernetes TLS Secret, expose port 8883 through an appropriate load balancer, disable plaintext port 1883, and add a PodDisruptionBudget.
