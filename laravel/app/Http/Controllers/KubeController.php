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
        $items = $response->json()['items'];

        $formatted = collect($items)->map(function ($pod) {
            $metadata = $pod['metadata'];
            $status = $pod['status'];
            $spec = $pod['spec'];

            return [
                'name' => $metadata['name'],
                'namespace' => $metadata['namespace'],
                'created_at' => $metadata['creationTimestamp'],
                'status' => $status['phase'] ?? 'Unknown',
                'node' => $spec['nodeName'] ?? 'N/A',
                'ip' => $status['podIP'] ?? 'N/A',
                'containers' => array_map(fn($c) => $c['name'], $spec['containers']),
            ];
        });

        return response()->json($formatted);
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
        'containerName' => 'required|string',
        'port' => 'nullable|integer'
    ]);

    $body = [
        'apiVersion' => 'v1',
        'kind' => 'Pod',
        'metadata' => [
            'name' => $request->name,
            'namespace' => $request->namespace,
        ],
        'spec' => [
            'containers' => [[
                'name' => $request->containerName,
                'image' => $request->image,
                'ports' => $request->port ? [['containerPort' => (int)$request->port]] : []
            ]]
        ]
    ];

    try {
        $response = $this->kubeRequest(
            "/api/v1/namespaces/{$request->namespace}/pods",
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
            'error' => 'Failed to create pod',
            'message' => $e->getMessage(),
            'request_body' => $body
        ], 500);
    }
}

public function deletePod($namespace, $name)
{
    try {
        $response = $this->kubeRequest(
            "/api/v1/namespaces/{$namespace}/pods/{$name}",
            'DELETE'
        );

        return response()->json(['message' => 'Pod deleted'], $response->status());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to delete pod',
            'message' => $e->getMessage()
        ], 500);
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
        'json' => 'required|array' // Now we only accept JSON
    ]);

    try {
        $body = $request->json;

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
        
        // Ensure containers is an array and securityContext is an object
        if (isset($body['spec'])) {
            $this->ensureContainersIsArray($body['spec']);
            $this->formatSecurityContext($body['spec']);
            
            // Format container resources properly
            if (isset($body['spec']['containers']) && is_array($body['spec']['containers'])) {
                foreach ($body['spec']['containers'] as &$container) {
                    $this->formatContainerResources($container);
                }
            }
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
            'error' => 'Pod update failed',
            'message' => $e->getMessage(),
            'details' => $e instanceof \Symfony\Component\Yaml\Exception\ParseException 
                ? $e->getParsedLine() 
                : null
        ], 500);
    }
}

private function cleanPodManifest($manifest)
{
    // Remove immutable fields
    unset(
        $manifest['status'],
        $manifest['metadata']['uid'],
        $manifest['metadata']['resourceVersion'],
        $manifest['metadata']['managedFields'],
        $manifest['metadata']['creationTimestamp']
    );

    // Ensure containers is an array, securityContext is an object, and format resources properly
    if (isset($manifest['spec'])) {
        $this->ensureContainersIsArray($manifest['spec']);
        $this->formatSecurityContext($manifest['spec']);
        
        if (isset($manifest['spec']['containers']) && is_array($manifest['spec']['containers'])) {
            foreach ($manifest['spec']['containers'] as &$container) {
                $this->formatContainerResources($container);
            }
        }
    }

    return $manifest;
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


public function getService($namespace, $name)
{
    try {
        $response = $this->kubeRequest("/api/v1/namespaces/{$namespace}/services/{$name}");
        return response()->json($response->json());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to get service',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function updateService(Request $request, $namespace, $name)
{
    $request->validate([
        'yaml' => 'required_without:json|string',
        'json' => 'required_without:yaml|array'
    ]);

    try {
        $body = $request->has('json') 
            ? $request->json 
            : $this->parseYamlWithTimestamps(trim($request->yaml));

        // Remove immutable fields
        unset(
            $body['status'],
            $body['metadata']['uid'],
            $body['metadata']['resourceVersion'],
            $body['metadata']['managedFields'],
            $body['metadata']['creationTimestamp']
        );

        // Validate structure
        if (!isset($body['apiVersion'], $body['kind'], $body['metadata'], $body['spec'])) {
            throw new \Exception("Missing required Kubernetes fields");
        }

        $response = $this->kubeRequest(
            "/api/v1/namespaces/{$namespace}/services/{$name}",
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
            'error' => 'Service update failed',
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

    public function getNamespace(string $name)
    {
        try {
            $response = $this->kubeRequest("/api/v1/namespaces/{$name}");
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve namespace', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateNamespace(Request $request, string $name)
    {
        $request->validate([
            'yaml' => 'required_without:json|string',
            'json' => 'required_without:yaml|array'
        ]);

        try {
            $body = $request->has('json') 
                ? $request->json 
                : $this->parseYamlWithTimestamps(trim($request->yaml));

            // Remove immutable fields
            unset(
                $body['status'],
                $body['metadata']['uid'],
                $body['metadata']['resourceVersion'],
                $body['metadata']['managedFields'],
                $body['metadata']['creationTimestamp']
            );

            // Validate structure
            if (!isset($body['apiVersion'], $body['kind'], $body['metadata'])) {
                throw new \Exception("Missing required Kubernetes fields");
            }

            // Check API version and kind
            if ($body['apiVersion'] !== 'v1') {
                throw new \Exception("Namespace in version \"{$body['apiVersion']}\" cannot be handled as a Namespace: no kind \"Namespace\" is registered for version \"{$body['apiVersion']}\" in scheme");
            }
            
            if ($body['kind'] !== 'Namespace') {
                throw new \Exception("Resource of type \"{$body['kind']}\" cannot be handled as a Namespace");
            }

            // Ensure we're updating the correct resource
            if (($body['metadata']['name'] ?? null) !== $name) {
                throw new \Exception("Cannot change resource name");
            }

            $response = $this->kubeRequest(
                "/api/v1/namespaces/{$name}",
                'PUT',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                    'body' => json_encode($body, JSON_UNESCAPED_SLASHES)
                ]
            );
            
            $responseData = $response->json();
            
            // Check if the response contains any error status
            if ($response->status() >= 400 || 
                (isset($responseData['status']) && $responseData['status'] === 'Failure')) {
                return response()->json([
                    'error' => 'Namespace update failed',
                    'message' => $responseData['message'] ?? 'Unknown error from Kubernetes API',
                    'details' => $responseData
                ], $response->status() >= 400 ? $response->status() : 422);
            }

            return response()->json($responseData, $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Namespace update failed',
                'message' => $e->getMessage(),
                'details' => $e instanceof \Symfony\Component\Yaml\Exception\ParseException 
                    ? $e->getParsedLine() 
                    : null
            ], 500);
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

   public function getDeployment($namespace, $name)
{
    try {
        $response = $this->kubeRequest("/apis/apps/v1/namespaces/{$namespace}/deployments/{$name}");
        return response()->json($response->json());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to get deployment',
            'message' => $e->getMessage()
        ], 500);
    }
}

/**
 * Helper function to properly format container resources
 * This ensures resources are properly formatted for Kubernetes API
 */
private function formatContainerResources(&$container)
{
    if (!isset($container['resources'])) {
        return;
    }
    
    // If resources is an empty array, convert to empty object
    if (is_array($container['resources']) && empty($container['resources'])) {
        $container['resources'] = (object) [];
        return;
    }
    
    // If resources is not an array or object, set to empty object
    if (!is_array($container['resources']) && !is_object($container['resources'])) {
        $container['resources'] = (object) [];
        return;
    }
    
    // Handle limits and requests
    if (isset($container['resources']['limits'])) {
        // If limits is an empty array, convert to empty object
        if (is_array($container['resources']['limits']) && empty($container['resources']['limits'])) {
            $container['resources']['limits'] = (object) [];
        }
        // If limits is not an array or object but exists, convert to empty object
        elseif (!is_array($container['resources']['limits']) && !is_object($container['resources']['limits'])) {
            $container['resources']['limits'] = (object) [];
        }
    }
    
    if (isset($container['resources']['requests'])) {
        // If requests is an empty array, convert to empty object
        if (is_array($container['resources']['requests']) && empty($container['resources']['requests'])) {
            $container['resources']['requests'] = (object) [];
        }
        // If requests is not an array or object but exists, convert to empty object
        elseif (!is_array($container['resources']['requests']) && !is_object($container['resources']['requests'])) {
            $container['resources']['requests'] = (object) [];
        }
    }
}

/**
 * Ensure containers field is always an array
 */
private function ensureContainersIsArray(&$spec)
{
    // If containers doesn't exist, create an empty array
    if (!isset($spec['containers'])) {
        $spec['containers'] = [];
        return;
    }
    
    // If containers is not an array, but is an object, convert to array
    if (!is_array($spec['containers']) && is_object($spec['containers'])) {
        // Convert object to array
        $containersArray = [];
        foreach ($spec['containers'] as $key => $value) {
            if (is_numeric($key)) {
                $containersArray[(int)$key] = $value;
            } else {
                // If it's an associative object, it might be a single container
                $containersArray[] = $spec['containers'];
                break;
            }
        }
        $spec['containers'] = $containersArray;
    }
    // If containers is not an array and not an object, make it an empty array
    else if (!is_array($spec['containers'])) {
        $spec['containers'] = [];
    }
}

/**
 * Ensure securityContext field is always an object
 */
private function formatSecurityContext(&$spec)
{
    // Handle securityContext at pod level
    if (isset($spec['securityContext'])) {
        // If securityContext is an array, convert to object
        if (is_array($spec['securityContext']) && empty($spec['securityContext'])) {
            $spec['securityContext'] = (object) [];
        } elseif (is_array($spec['securityContext'])) {
            $spec['securityContext'] = (object) $spec['securityContext'];
        }
    }
    
    // Handle securityContext at container level
    if (isset($spec['containers']) && is_array($spec['containers'])) {
        foreach ($spec['containers'] as &$container) {
            if (isset($container['securityContext'])) {
                // If securityContext is an array, convert to object
                if (is_array($container['securityContext']) && empty($container['securityContext'])) {
                    $container['securityContext'] = (object) [];
                } elseif (is_array($container['securityContext'])) {
                    $container['securityContext'] = (object) $container['securityContext'];
                }
            }
        }
    }
}

public function updateDeployment(Request $request, $namespace, $name)
{
    $request->validate([
        'yaml' => 'required_without:json|string',
        'json' => 'required_without:yaml|array'
    ]);

    try {
        $body = $request->has('json') 
            ? $request->json 
            : $this->parseYamlWithTimestamps(trim($request->yaml));

        // Remove immutable fields
        unset(
            $body['status'],
            $body['metadata']['uid'],
            $body['metadata']['resourceVersion'],
            $body['metadata']['managedFields'],
            $body['metadata']['creationTimestamp']
        );

        // Validate structure
        if (!isset($body['apiVersion'], $body['kind'], $body['metadata'], $body['spec'])) {
            throw new \Exception("Missing required Kubernetes fields");
        }

        // Check API version and kind
        if ($body['apiVersion'] !== 'apps/v1') {
            throw new \Exception("Deployment in version \"{$body['apiVersion']}\" cannot be handled as a Deployment: no kind \"Deployment\" is registered for version \"{$body['apiVersion']}\" in scheme");
        }
        
        if ($body['kind'] !== 'Deployment') {
            throw new \Exception("Resource of type \"{$body['kind']}\" cannot be handled as a Deployment");
        }

        // Ensure containers is an array and securityContext is an object
        if (isset($body['spec']['template']['spec'])) {
            $this->ensureContainersIsArray($body['spec']['template']['spec']);
            $this->formatSecurityContext($body['spec']['template']['spec']);
            
            // Fix resources format in containers
            if (isset($body['spec']['template']['spec']['containers']) && is_array($body['spec']['template']['spec']['containers'])) {
                foreach ($body['spec']['template']['spec']['containers'] as &$container) {
                    $this->formatContainerResources($container);
                }
            }
        }

        // Use JSON_UNESCAPED_SLASHES but NOT JSON_FORCE_OBJECT to ensure arrays remain arrays
        $response = $this->kubeRequest(
            "/apis/apps/v1/namespaces/{$namespace}/deployments/{$name}",
            'PUT',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body' => json_encode($body, JSON_UNESCAPED_SLASHES)
            ]
        );
        
        $responseData = $response->json();
        
        // Check if the response contains any error status
        if ($response->status() >= 400 || 
            (isset($responseData['status']) && $responseData['status'] === 'Failure')) {
            return response()->json([
                'error' => 'Deployment update failed',
                'message' => $responseData['message'] ?? 'Unknown error from Kubernetes API',
                'details' => $responseData
            ], $response->status() >= 400 ? $response->status() : 422);
        }

        return response()->json($responseData, $response->status());
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Deployment update failed',
            'message' => $e->getMessage(),
            'details' => $e instanceof \Symfony\Component\Yaml\Exception\ParseException 
                ? $e->getParsedLine() 
                : null
        ], 500);
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
        
        $responseData = $response->json();
        
        // Check if the response contains any error status
        if ($response->status() >= 400 || 
            (isset($responseData['status']) && $responseData['status'] === 'Failure')) {
            return response()->json([
                'error' => 'Ingress update failed',
                'message' => $responseData['message'] ?? 'Unknown error from Kubernetes API',
                'details' => $responseData
            ], $response->status() >= 400 ? $response->status() : 422);
        }

        return response()->json($responseData, $response->status());
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
