<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirestoreService
{
    public $projectId;
    public $accessToken;
    public $baseUrl;

    public function __construct()
    {
        $this->projectId = config('firebase.project_id');
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";

        try {
            $this->accessToken = $this->getAccessToken();
        } catch (Exception $e) {
            Log::error('Failed to initialize Firestore REST client', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function collection(string $collectionName)
    {
        return new class($this, $collectionName) {
            protected $firestore;
            protected $collectionName;
            protected $path;

            public function __construct($firestore, $collectionName)
            {
                $this->firestore = $firestore;
                $this->collectionName = $collectionName;
                $this->path = $collectionName;
            }

            public function document(string $documentId)
            {
                return new class($this->firestore, $this->path, $documentId) {
                    protected $firestore;
                    protected $path;
                    protected $documentId;

                    public function __construct($firestore, $path, $documentId)
                    {
                        $this->firestore = $firestore;
                        $this->path = $path;
                        $this->documentId = $documentId;
                    }

                    public function collection(string $subCollectionName)
                    {
                        return new class($this->firestore, $this->path, $this->documentId, $subCollectionName) {
                            protected $firestore;
                            protected $path;
                            protected $documentId;
                            protected $subCollectionName;

                            public function __construct($firestore, $path, $documentId, $subCollectionName)
                            {
                                $this->firestore = $firestore;
                                $this->path = "{$path}/{$documentId}/{$subCollectionName}";
                                $this->documentId = $documentId;
                                $this->subCollectionName = $subCollectionName;
                            }

                            public function document(string $subDocumentId)
                            {
                                return new class($this->firestore, $this->path, $subDocumentId) {
                                    protected $firestore;
                                    protected $path;
                                    protected $documentId;

                                    public function __construct($firestore, $path, $documentId)
                                    {
                                        $this->firestore = $firestore;
                                        $this->path = $path;
                                        $this->documentId = $documentId;
                                    }

                                    public function snapshot()
                                    {
                                        $url = "{$this->firestore->baseUrl}/{$this->path}/{$this->documentId}";
                                        $response = Http::withToken($this->firestore->accessToken)->get($url);

                                        if ($response->successful()) {
                                            $data = $response->json();
                                            return new class($data, $this->firestore) {
                                                protected $data;
                                                protected $exists;
                                                protected $firestore;

                                                public function __construct($data, $firestore)
                                                {
                                                    $this->data = $data;
                                                    $this->exists = isset($data['fields']);
                                                    $this->firestore = $firestore;
                                                }

                                                public function exists()
                                                {
                                                    return $this->exists;
                                                }

                                                public function data()
                                                {
                                                    if (!$this->exists) {
                                                        return [];
                                                    }
                                                    return $this->firestore->parseDocumentData($this->data);
                                                }
                                            };
                                        } else {
                                            return new class(false) {
                                                protected $exists;

                                                public function __construct($exists)
                                                {
                                                    $this->exists = $exists;
                                                }

                                                public function exists()
                                                {
                                                    return $this->exists;
                                                }

                                                public function data()
                                                {
                                                    return [];
                                                }
                                            };
                                        }
                                    }
                                };
                            }
                        };
                    }

                    public function snapshot()
                    {
                        $url = "{$this->firestore->baseUrl}/{$this->path}/{$this->documentId}";
                        $response = Http::withToken($this->firestore->accessToken)->get($url);

                        if ($response->successful()) {
                            $data = $response->json();
                            return new class($data, $this->firestore) {
                                protected $data;
                                protected $exists;
                                protected $firestore;

                                public function __construct($data, $firestore)
                                {
                                    $this->data = $data;
                                    $this->exists = isset($data['fields']);
                                    $this->firestore = $firestore;
                                }

                                public function exists()
                                {
                                    return $this->exists;
                                }

                                public function data()
                                {
                                    if (!$this->exists) {
                                        return [];
                                    }
                                    return $this->firestore->parseDocumentData($this->data);
                                }
                            };
                        } else {
                            return new class(false) {
                                protected $exists;

                                public function __construct($exists)
                                {
                                    $this->exists = $exists;
                                }

                                public function exists()
                                {
                                    return $this->exists;
                                }

                                public function data()
                                {
                                    return [];
                                }
                            };
                        }
                    }
                };
            }
        };
    }

    protected function getAccessToken()
    {
        $serviceAccountPath = config('firebase.credentials.file');

        if (!file_exists($serviceAccountPath)) {
            throw new Exception("Service account file not found at: {$serviceAccountPath}");
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (!$serviceAccount) {
            throw new Exception("Invalid service account file");
        }

        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $payload = json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => time() + 3600,
            'iat' => time()
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = '';
        $privateKey = $serviceAccount['private_key'];
        openssl_sign($base64Header . '.' . $base64Payload, $signature, $privateKey, 'SHA256');
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to get access token: " . $response->body());
        }

        $tokenData = $response->json();
        return $tokenData['access_token'];
    }

    public function getDocument(string $collectionName, string $documentId)
    {
        try {
            $url = "{$this->baseUrl}/{$collectionName}/{$documentId}";
            $response = Http::withToken($this->accessToken)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return $this->parseDocumentData($data);
            } elseif ($response->status() === 404) {
                return null;
            } else {
                throw new Exception("Failed to get document: " . $response->body());
            }
        } catch (Exception $e) {
            Log::error("Failed to get document {$collectionName}/{$documentId}", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getDocuments(string $collectionName)
    {
        try {
            $url = "{$this->baseUrl}/{$collectionName}";
            $response = Http::withToken($this->accessToken)->get($url);

            if (!$response->successful()) {
                throw new Exception("Failed to get documents: " . $response->body());
            }

            $data = $response->json();
            $result = [];

            if (isset($data['documents'])) {
                foreach ($data['documents'] as $document) {
                    $documentId = basename($document['name']);
                    $result[$documentId] = $this->parseDocumentData($document);
                }
            }

            return $result;
        } catch (Exception $e) {
            Log::error("Failed to get documents from {$collectionName}", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function setDocument(string $collectionName, string $documentId, array $data)
    {
        try {
            $url = "{$this->baseUrl}/{$collectionName}/{$documentId}";
            $firestoreData = $this->convertToFirestoreFormat($data);

            $response = Http::withToken($this->accessToken)->patch($url, [
                'fields' => $firestoreData
            ]);

            if (!$response->successful()) {
                throw new Exception("Failed to set document: " . $response->body());
            }

            return ['status' => 'success', 'message' => "Document {$documentId} has been set in {$collectionName}"];
        } catch (Exception $e) {
            Log::error("Failed to set document {$collectionName}/{$documentId}", [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function updateDocument(string $collectionName, string $documentId, array $data)
    {
        try {
            $url = "{$this->baseUrl}/{$collectionName}/{$documentId}";
            $firestoreData = $this->convertToFirestoreFormat($data);

            $response = Http::withToken($this->accessToken)->patch($url, [
                'fields' => $firestoreData
            ]);

            if (!$response->successful()) {
                throw new Exception("Failed to update document: " . $response->body());
            }

            return ['status' => 'success', 'message' => "Document {$documentId} has been updated in {$collectionName}"];
        } catch (Exception $e) {
            Log::error("Failed to update document {$collectionName}/{$documentId}", [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function deleteDocument(string $collectionName, string $documentId)
    {
        try {
            $url = "{$this->baseUrl}/{$collectionName}/{$documentId}";
            $response = Http::withToken($this->accessToken)->delete($url);

            if (!$response->successful()) {
                throw new Exception("Failed to delete document: " . $response->body());
            }

            return ['status' => 'success', 'message' => "Document {$documentId} has been deleted from {$collectionName}"];
        } catch (Exception $e) {
            Log::error("Failed to delete document {$collectionName}/{$documentId}", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function query(string $collectionName, array $conditions)
    {
        try {
            $url = "{$this->baseUrl}:runQuery";
            $query = [
                'structuredQuery' => [
                    'from' => [['collectionId' => $collectionName]]
                ]
            ];

            if (!empty($conditions)) {
                $wheres = [];
            foreach ($conditions as $condition) {
                if (count($condition) === 3) {
                    [$field, $operator, $value] = $condition;
                        $wheres[] = [
                            'fieldFilter' => [
                                'field' => ['fieldPath' => $field],
                                'op' => $this->convertOperator($operator),
                                'value' => $this->convertToFirestoreValue($value)
                            ]
                        ];
                    }
                }

                if (count($wheres) === 1) {
                    $query['structuredQuery']['where'] = $wheres[0];
                } else {
                    $query['structuredQuery']['where'] = [
                        'compositeFilter' => [
                            'op' => 'AND',
                            'filters' => $wheres
                        ]
                    ];
                }
            }

            $response = Http::withToken($this->accessToken)->post($url, $query);

            if (!$response->successful()) {
                throw new Exception("Failed to query documents: " . $response->body());
            }

            $data = $response->json();
            $result = [];

            if (isset($data[0]['document'])) {
                foreach ($data as $item) {
                    if (isset($item['document'])) {
                        $document = $item['document'];
                        $documentId = basename($document['name']);
                        $result[$documentId] = $this->parseDocumentData($document);
                    }
                }
            }

            return $result;
        } catch (Exception $e) {
            Log::error("Failed to query documents from {$collectionName}", [
                'message' => $e->getMessage(),
                'conditions' => $conditions
            ]);
            throw $e;
        }
    }

    public function convertToFirestoreFormat(array $data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = $this->convertToFirestoreValue($value);
        }
        return $result;
    }

    public function convertToFirestoreValue($value)
    {
        if (is_string($value)) {
            return ['stringValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => (string)$value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                return ['arrayValue' => ['values' => array_map([$this, 'convertToFirestoreValue'], $value)]];
            } else {
                return ['mapValue' => ['fields' => $this->convertToFirestoreFormat($value)]];
            }
        } elseif (is_null($value)) {
            return ['nullValue' => null];
        } else {
            return ['stringValue' => (string)$value];
        }
    }

    public function parseDocumentData(array $document)
    {
        if (!isset($document['fields'])) {
            return [];
        }

        $result = [];
        foreach ($document['fields'] as $key => $value) {
            $result[$key] = $this->parseFirestoreValue($value);
        }
        return $result;
    }

    public function parseFirestoreValue(array $value)
    {
        if (isset($value['stringValue'])) {
            return $value['stringValue'];
        } elseif (isset($value['integerValue'])) {
            return (int)$value['integerValue'];
        } elseif (isset($value['doubleValue'])) {
            return (float)$value['doubleValue'];
        } elseif (isset($value['booleanValue'])) {
            return $value['booleanValue'];
        } elseif (isset($value['arrayValue'])) {
            $result = [];
            if (isset($value['arrayValue']['values'])) {
                foreach ($value['arrayValue']['values'] as $item) {
                    $result[] = $this->parseFirestoreValue($item);
                }
            }
            return $result;
        } elseif (isset($value['mapValue'])) {
            $result = [];
            if (isset($value['mapValue']['fields'])) {
                foreach ($value['mapValue']['fields'] as $key => $item) {
                    $result[$key] = $this->parseFirestoreValue($item);
                }
            }
            return $result;
        } elseif (isset($value['nullValue'])) {
            return null;
        } else {
            return null;
        }
    }

    public function convertOperator(string $operator)
    {
        $operators = [
            '=' => 'EQUAL',
            '!=' => 'NOT_EQUAL',
            '<' => 'LESS_THAN',
            '<=' => 'LESS_THAN_OR_EQUAL',
            '>' => 'GREATER_THAN',
            '>=' => 'GREATER_THAN_OR_EQUAL',
            'in' => 'IN',
            'not-in' => 'NOT_IN',
            'array-contains' => 'ARRAY_CONTAINS',
            'array-contains-any' => 'ARRAY_CONTAINS_ANY'
        ];

        return $operators[$operator] ?? 'EQUAL';
    }
}
