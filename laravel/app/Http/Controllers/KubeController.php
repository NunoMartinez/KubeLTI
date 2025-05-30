<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Yaml\Yaml;

class KubeController extends Controller
{
    private $baseUrl = 'https://172.17.0.1:16443';

    private function kubeRequest(string $endpoint, string $method = 'GET', array $options = [])
    {
        $token = trim(env('KUBE_TOKEN'));

        return Http::baseUrl($this->baseUrl)
            ->withToken($token)
            ->withoutVerifying()
            ->withOptions($options)
            ->send($method, $endpoint);
    }

    public function proxy()
    {
        try {
            $response = $this->kubeRequest('/api');
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to connect to Kubernetes API',
                'message' => $e->getMessage()
            ], 500);
        }
    }


        // METRICS \\

    public function clusterMetrics()
{
    try {
        // Get node metrics
        $nodeMetrics = $this->kubeRequest('/apis/metrics.k8s.io/v1beta1/nodes')->json();
        $nodeList = $this->kubeRequest('/api/v1/nodes')->json();
        
        // Calculate total resources
        $metrics = [
            'cpu' => ['total' => 0, 'used' => 0],
            'memory' => ['total' => 0, 'used' => 0],
            'pods' => ['total' => 0, 'running' => 0]
        ];
        
        // Process node metrics
        foreach ($nodeList['items'] as $node) {
            $metrics['cpu']['total'] += (int)$node['status']['capacity']['cpu'];
            $metrics['memory']['total'] += $this->convertToGB($node['status']['capacity']['memory']);
            $metrics['pods']['total'] += (int)$node['status']['capacity']['pods'];
        }
        
        foreach ($nodeMetrics['items'] as $metric) {
            $metrics['cpu']['used'] += $this->parseCpu($metric['usage']['cpu']);
            $metrics['memory']['used'] += $this->convertToGB($metric['usage']['memory']);
        }
        
        // Get pod count
        $podMetrics = $this->kubeRequest('/apis/metrics.k8s.io/v1beta1/pods')->json();
        $metrics['pods']['running'] = count($podMetrics['items']);
        
        return response()->json($metrics);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

private function convertToGB($memory)
{
    if (str_contains($memory, 'Ki')) {
        return (int)filter_var($memory, FILTER_SANITIZE_NUMBER_INT) / 1024 / 1024;
    }
    // Add other conversions if needed
    return 0;
}

private function parseCpu($cpu)
{
    if (str_ends_with($cpu, 'n')) {
        return (int)filter_var($cpu, FILTER_SANITIZE_NUMBER_INT) / 1000000000;
    }
    return (float)$cpu;
}




            // NODES \\


    public function nodes()
    {
        try {
            $response = $this->kubeRequest('/api/v1/nodes');
            $nodes = $response->json()['items'];

            $podResponse = $this->kubeRequest('/api/v1/pods');
        $allPods = $podResponse->json()['items'];
 // Count pods per node
        $podCounts = [];
        foreach ($allPods as $pod) {
            $nodeName = $pod['spec']['nodeName'] ?? null;
            if ($nodeName) {
                $podCounts[$nodeName] = ($podCounts[$nodeName] ?? 0) + 1;
            }
        }

        $formattedNodes = collect($nodes)->map(function ($node) use ($podCounts) {
                $labels = $node['metadata']['labels'] ?? [];
                $conditions = collect($node['status']['conditions']);
                $isReady = $conditions->firstWhere('type', 'Ready')['status'] === 'True';

                $cpu = $node['status']['capacity']['cpu'] ?? '0';
                $memoryKi = $node['status']['capacity']['memory'] ?? '0';
                $memoryGb = round(((int) filter_var($memoryKi, FILTER_SANITIZE_NUMBER_INT)) / 1024 / 1024, 1);

                $taints = $node['spec']['taints'] ?? [];
                $role = 'Worker';

                foreach ($taints as $taint) {
                    if (
                        str_contains($taint['key'], 'master') ||
                        str_contains($taint['key'], 'control-plane')
                    ) {
                        $role = 'Master';
                        break;
                    }
                }

                return [
                    'name' => $node['metadata']['name'],
                    'status' => $isReady ? 'Online' : 'Offline',
                    'role' => $role,
                    'kubeletVersion' => $node['status']['nodeInfo']['kubeletVersion'] ?? 'N/A',
                    'cpu' => $cpu,
                    'memory' => $memoryGb . ' GB',
                    'podCount' => $podCounts[$node['metadata']['name']] ?? 0,
                    'podCapacity' => (int) ($node['status']['capacity']['pods'] ?? 0),
                ];
            });

            return response()->json($formattedNodes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve nodes', 'message' => $e->getMessage()], 500);
        }
    }


                //  PODS  \\


    public function pods()
    {
        try {
            $response = $this->kubeRequest('/api/v1/pods');
            $pods = $response->json()['items'];

            $formattedPods = collect($pods)->map(function ($pod) {
                $status = $pod['status']['phase'] ?? 'Unknown';
                $restarts = collect($pod['status']['containerStatuses'] ?? [])
                    ->sum(fn($cs) => $cs['restartCount'] ?? 0);

                return [
                    'name' => $pod['metadata']['name'],
                    'namespace' => $pod['metadata']['namespace'],
                    'status' => $status,
                    'nodeName' => $pod['spec']['nodeName'] ?? 'N/A',
                    'restarts' => $restarts,
                ];
            });

            return response()->json($formattedPods);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve pods', 'message' => $e->getMessage()], 500);
        }
    }

public function createPod(Request $request)
{
    $request->validate([
        'namespace' => 'required|string',
        'name' => 'required|string',
        'image' => 'required|string',
        // Add more validation if needed
    ]);

    $body = [
        'apiVersion' => 'v1',
        'kind' => 'Pod',
        'metadata' => [
            'name' => $request->name,
            'namespace' => $request->namespace,  // include namespace here, safer
        ],
        'spec' => [
            'containers' => [[
                'name' => $request->name,
                'image' => $request->image,
                // optionally add 'imagePullPolicy', 'ports', etc. here if you want
            ]],
            'restartPolicy' => 'Always', // default restart policy, adjust as needed
        ],
    ];

    try {
        $response = $this->kubeRequest(
            "/api/v1/namespaces/{$request->namespace}/pods",
            'POST',
            [
                'body' => json_encode($body),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    // Add Authorization if kubeRequest does not add it internally
                ],
            ]
        );

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $respBody = $response->getBody()->getContents();
            return response()->json(['error' => 'Kubernetes API error', 'details' => json_decode($respBody, true)], $response->getStatusCode());
        }

        return response()->json(['message' => 'Pod created'], 201);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to create pod', 'message' => $e->getMessage()], 500);
    }
}


public function deletePod(string $namespace, string $name)
{
    try {
        $response = $this->kubeRequest("/api/v1/namespaces/{$namespace}/pods/{$name}", 'DELETE');

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $respBody = $response->getBody()->getContents();
            return response()->json(['error' => 'Kubernetes API error', 'details' => json_decode($respBody, true)], $response->getStatusCode());
        }

        return response()->json(['message' => 'Pod deleted'], $response->getStatusCode());
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete pod', 'message' => $e->getMessage()], 500);
    }
}

public function getPod($namespace, $name)
{
    try {
        $response = $this->kubeRequest(
            "/api/v1/namespaces/{$namespace}/pods/{$name}"
        );
        return response()->json($response->json());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to get pod',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function updatePod(Request $request, $namespace, $name)
{
    $request->validate([
        'yaml' => 'required_without:json|string',
        'json' => 'required_without:yaml|array'
    ]);

    try {
        if ($request->has('json')) {
            $body = $request->json;
        } else {
            $yamlContent = trim($request->yaml);
            if (empty($yamlContent)) {
                throw new \Exception("YAML input cannot be empty");
            }

            $body = $this->parseYamlWithTimestamps($yamlContent);
        }

        // Convert all timestamps to strings
        $body = $this->normalizePodTimestamps($body);
      
        $body = $this->normalizePodResources($body);
        // Remove immutable/status fields
        unset($body['status']);
        unset($body['metadata']['managedFields']);
        unset($body['metadata']['uid']);
        unset($body['metadata']['resourceVersion']);

        // Validate structure
        if (!isset($body['apiVersion'], $body['kind'], $body['metadata'], $body['spec'])) {
            throw new \Exception("Missing required Kubernetes fields");
        }

        $response = $this->kubeRequest(
            "/api/v1/namespaces/{$namespace}/pods/{$name}",
            'PUT',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body' => json_encode($body, JSON_UNESCAPED_SLASHES)
            ]
        );

        return response()->json($response->json(), $response->status());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Update failed',
            'message' => $e->getMessage()
        ], 500);
    }
}

private function normalizePodTimestamps($data)
{
    $timestampFields = [
        'creationTimestamp',
        'lastProbeTime',
        'lastTransitionTime',
        'finishedAt',
        'startedAt',
        'startTime'
    ];

    array_walk_recursive($data, function (&$value, $key) use ($timestampFields) {
        if (in_array($key, $timestampFields)) {
            if (is_numeric($value)) {
                $value = date('Y-m-d\TH:i:s\Z', $value);
            } elseif ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d\TH:i:s\Z');
            }
        }
    });

    return $data;
}

private function normalizePodResources($data)
{
    if (isset($data['spec']['containers'])) {
        foreach ($data['spec']['containers'] as &$container) {
            if (isset($container['resources']) && is_array($container['resources']) && empty($container['resources'])) {
                $container['resources'] = new \stdClass(); // Converts to {} in JSON
            }
        }
    }
    return $data;
}
 






            //  Services \\


    public function services()
    {
        try {
            $response = $this->kubeRequest('/api/v1/services');
            $services = $response->json()['items'];

            $formattedServices = collect($services)->map(function ($service) {
                return [
                    'name' => $service['metadata']['name'],
                    'namespace' => $service['metadata']['namespace'],
                    'type' => $service['spec']['type'] ?? 'ClusterIP',
                    'clusterIP' => $service['spec']['clusterIP'] ?? 'N/A',
                    'ports' => collect($service['spec']['ports'] ?? [])->map(fn($port) => $port['port'])->join(', '),
                    'selector' => $service['spec']['selector'] ?? [],
                ];
            });

            return response()->json($formattedServices);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve services', 'message' => $e->getMessage()], 500);
        }
    }

public function createService(Request $request)
{
    $request->validate([
        'namespace' => 'required|string',
        'name' => 'required|string',
        'type' => 'required|in:ClusterIP,NodePort,LoadBalancer',
        'port' => 'required|integer|min:1|max:65535',
        'targetPort' => 'required|integer|min:1|max:65535',
        'selector' => 'required|array'
    ]);

    $body = [
        'apiVersion' => 'v1',
        'kind' => 'Service',
        'metadata' => [
            'name' => $request->name,
            'namespace' => $request->namespace
        ],
        'spec' => [
            'type' => $request->type,
            'ports' => [[
                'port' => (int)$request->port,
                'targetPort' => (int)$request->targetPort
            ]],
            'selector' => $request->selector
        ]
    ];

    try {
        $response = $this->kubeRequest(
            "/api/v1/namespaces/{$request->namespace}/services",
            'POST',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body' => json_encode($body)
            ]
        );

        return response()->json($response->json(), $response->status());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to create service',
            'message' => $e->getMessage(),
            'request_body' => $body // For debugging
        ], 500);
    }
}

public function deleteService($namespace, $name)
{
    try {
        $response = $this->kubeRequest(
            "/api/v1/namespaces/{$namespace}/services/{$name}",
            'DELETE'
        );

        return response()->json(['message' => 'Service deleted'], $response->status());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to delete service',
            'message' => $e->getMessage()
        ], 500);
    }
}







            //  NAMESPACES  \\

    public function namespaces()
    {
        try {
            $response = $this->kubeRequest('/api/v1/namespaces');
            $namespaces = $response->json()['items'];

            $formatted = collect($namespaces)->map(function ($ns) {
                return [
                    'name' => $ns['metadata']['name'],
                    'status' => $ns['status']['phase'] ?? 'Unknown',
                    'created_at' => $ns['metadata']['creationTimestamp'] ?? null,
                ];
            });

            return response()->json($formatted);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve namespaces', 'message' => $e->getMessage()], 500);
        }
    }

    public function createNamespace(Request $request)
    {
        $name = $request->input('name');

        if (!$name) {
            return response()->json(['error' => 'Namespace name is required'], 400);
        }

        $body = ['metadata' => ['name' => $name]];

        try {
            $response = $this->kubeRequest('/api/v1/namespaces', 'POST', [
                'body' => json_encode($body),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create namespace', 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteNamespace(string $name)
    {
        try {
            $response = $this->kubeRequest("/api/v1/namespaces/{$name}", 'DELETE');
            return response()->json(['message' => 'Namespace deleted'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete namespace', 'message' => $e->getMessage()], 500);
        }
    }



            //  DEPLOYMENTS  \\


    public function deployments()
    {
        try {
            $response = $this->kubeRequest('/apis/apps/v1/deployments');
            $items = $response->json()['items'];

            $formatted = collect($items)->map(function ($d) {
                return [
                    'name' => $d['metadata']['name'],
                    'namespace' => $d['metadata']['namespace'],
                    'replicas' => $d['spec']['replicas'] ?? 0,
                    'available' => $d['status']['availableReplicas'] ?? 0,
                    'created_at' => $d['metadata']['creationTimestamp'],
                    'labels' => $d['metadata']['labels'] ?? [],
                ];
            });

            return response()->json($formatted);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve deployments', 'message' => $e->getMessage()], 500);
        }
    }

    public function createDeployment(Request $request)
    {
        $request->validate([
            'namespace' => 'required|string',
            'name' => 'required|string',
            'image' => 'required|string',
            'replicas' => 'required|integer|min:1',
        ]);

        $body = [
            'apiVersion' => 'apps/v1',
            'kind' => 'Deployment',
            'metadata' => ['name' => $request->name],
            'spec' => [
                'replicas' => $request->replicas,
                'selector' => ['matchLabels' => ['app' => $request->name]],
                'template' => [
                    'metadata' => ['labels' => ['app' => $request->name]],
                    'spec' => [
                        'containers' => [[
                            'name' => $request->name,
                            'image' => $request->image,
                        ]]
                    ]
                ]
            ]
        ];

        try {
            $response = $this->kubeRequest("/apis/apps/v1/namespaces/{$request->namespace}/deployments", 'POST', [
                'body' => json_encode($body),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Kubernetes API error',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ], $response->status());
            }

            return response()->json(['message' => 'Deployment created'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create deployment', 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteDeployment($namespace, $name)
    {
        try {
            $response = $this->kubeRequest("/apis/apps/v1/namespaces/{$namespace}/deployments/{$name}", 'DELETE');
            return response()->json(['message' => 'Deployment deleted'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete deployment', 'message' => $e->getMessage()], 500);
        }
    }



            //  INGRESS  \\


    public function ingresses()
    {
        try {
            $response = $this->kubeRequest('/apis/networking.k8s.io/v1/ingresses');
            $items = $response->json()['items'];

            $formatted = collect($items)->map(function ($ingress) {
                $metadata = $ingress['metadata'];
                $spec = $ingress['spec'];
                $rules = $spec['rules'] ?? [];

                $hosts = collect($rules)->map(fn($rule) => $rule['host'] ?? '')->filter()->values();

                return [
                    'name' => $metadata['name'],
                    'namespace' => $metadata['namespace'],
                    'created_at' => $metadata['creationTimestamp'],
                    'hosts' => $hosts,
                    'class' => $spec['ingressClassName'] ?? 'N/A',
                ];
            });

            return response()->json($formatted);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve ingresses', 'message' => $e->getMessage()], 500);
        }
    }

    public function createIngress(Request $request)
{
    $request->validate([
        'namespace' => 'required|string',
        'name' => 'required|string',
        'host' => 'required|string',
        'serviceName' => 'required|string',
        'servicePort' => 'required|integer',
        'ingressClass' => 'nullable|string'
    ]);

    $body = [
        'apiVersion' => 'networking.k8s.io/v1',
        'kind' => 'Ingress',
        'metadata' => [
            'name' => $request->name,
            'namespace' => $request->namespace,
            'annotations' => $request->ingressClass ? [
                'kubernetes.io/ingress.class' => $request->ingressClass
            ] : []
        ],
        'spec' => [
            'rules' => [[
                'host' => $request->host,
                'http' => [
                    'paths' => [[
                        'path' => '/',
                        'pathType' => 'Prefix',
                        'backend' => [
                            'service' => [
                                'name' => $request->serviceName,
                                'port' => [
                                    'number' => (int)$request->servicePort
                                ]
                            ]
                        ]
                    ]]
                ]
            ]]
        ]
    ];

    try {
        $response = $this->kubeRequest(
            "/apis/networking.k8s.io/v1/namespaces/{$request->namespace}/ingresses",
            'POST',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body' => json_encode($body)
            ]
        );

        return response()->json($response->json(), $response->status());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to create ingress',
            'message' => $e->getMessage(),
            'request_body' => $body
        ], 500);
    }
}

public function deleteIngress($namespace, $name)
{
    try {
        $response = $this->kubeRequest(
            "/apis/networking.k8s.io/v1/namespaces/{$namespace}/ingresses/{$name}",
            'DELETE'
        );

        return response()->json(['message' => 'Ingress deleted'], $response->status());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to delete ingress',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function getIngress($namespace, $name)
{
    try {
        $response = $this->kubeRequest(
            "/apis/networking.k8s.io/v1/namespaces/{$namespace}/ingresses/{$name}"
        );
        return response()->json($response->json());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to get ingress',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function updateIngress(Request $request, $namespace, $name)
{
    $request->validate([
        'yaml' => 'required_without:json|string',
        'json' => 'required_without:yaml|array'
    ]);

    try {
        if ($request->has('json')) {
            $body = $request->json;
        } else {
            $yamlContent = trim($request->yaml);
            if (empty($yamlContent)) {
                throw new \Exception("YAML input cannot be empty");
            }

            // Parse YAML with timestamp handling
            $body = $this->parseYamlWithTimestamps($yamlContent);
        }

        // Convert timestamps to strings
        $body = $this->normalizeTimestamps($body);

        // Validate structure
        if (!isset($body['apiVersion'], $body['kind'], $body['metadata'], $body['spec'])) {
            throw new \Exception("Missing required Kubernetes fields");
        }

        // Ensure we're updating the correct resource
        if (($body['metadata']['name'] ?? null) !== $name) {
            throw new \Exception("Cannot change resource name");
        }
        if (($body['metadata']['namespace'] ?? null) !== $namespace) {
            throw new \Exception("Cannot change resource namespace");
        }

        $response = $this->kubeRequest(
            "/apis/networking.k8s.io/v1/namespaces/{$namespace}/ingresses/{$name}",
            'PUT',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body' => json_encode($body, JSON_UNESCAPED_SLASHES)
            ]
        );

        return response()->json($response->json(), $response->status());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Update failed',
            'message' => $e->getMessage(),
            'details' => $e instanceof \Symfony\Component\Yaml\Exception\ParseException 
                ? $e->getParsedLine() 
                : null
        ], 500);
    }
}

private function parseYamlWithTimestamps($yamlContent)
{
    $body = \Symfony\Component\Yaml\Yaml::parse($yamlContent);
    
    // Convert DateTime objects to strings
    array_walk_recursive($body, function (&$value) {
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d\TH:i:s\Z');
        }
    });
    
    return $body;
}

private function normalizeTimestamps($data)
{
    $timestampFields = [
        'creationTimestamp',
        'time',
        'lastTransitionTime',
        'lastUpdateTime'
    ];

    array_walk_recursive($data, function (&$value, $key) use ($timestampFields) {
        if (in_array($key, $timestampFields) && !is_string($value)) {
            if (is_numeric($value)) {
                $value = date('Y-m-d\TH:i:s\Z', $value);
            } elseif ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d\TH:i:s\Z');
            }
        }
    });

    return $data;
}
}
