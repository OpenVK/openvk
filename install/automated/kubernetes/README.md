# Kubernetes deployment
- Open `manifests/001-configmap.yaml` in your favorite editor, point `websiteUrl` to your domain name, then generate unique `secret` value for `chandler.yml` section.
- Open `manifests/002-pvc.yaml` in your favorite editor and set necessary `annotations` for your storage class. Depending on your Kubernetes version, you may also need to set `storageClassName` in `spec` section.
- Open `manifests/005-ingress.yaml` in your favorite editor and set necessary `annotations` for your ingress controller, then point `host` to your domain name. Depending on your Kubernetes version, you may also need to set `ingressClassName` in `spec` section.
- (optional) if you don't want to use ingress (e.g. when testing locally w/ `minikube`), you can open `manifests/004-svc.yaml` and set `type: LoadBalancer` for `openvk-svc` and other services.
- (optional) if you don't need adminer or phpmyadmin, you can open `manifests/003-deployment.yaml` and set `replicas: 0` for `adminer` and `phpmyadmin` deployments.

## Apply order
Namespace:
```
kubectl apply -f manifests/000-ns.yaml
```
Configmap:
```
kubectl apply -f manifests/001-configmap.yaml
```
PVCs:
```
kubectl apply -f manifests/002-pvc.yaml
```
Deployments:
```
kubectl apply -f manifests/003-deployment.yaml
```
Services:
```
kubectl apply -f manifests/004-svc.yaml
```
Ingress:
```
kubectl apply -f manifests/005-ingress.yaml
```
Kafka (optional, enable in configmap first):
```
helm repo add bitnami https://charts.bitnami.com/bitnami
helm repo update
helm install kafka bitnami/kafka -n openvk -f manifests/006-kafka-values.yaml
```