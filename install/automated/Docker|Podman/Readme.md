Instruction for building with Podman|Docker:

For podman use:

podman build -t openvk .


podman run -it -p YOUR_EXTEND_PORT:80 openvk




For docker use:

docker build -t openvk .


docker run -it -p YOUR_EXTEND_PORT:80 --privileged openvk
