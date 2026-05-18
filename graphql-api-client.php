<?php

// Función centralizada para peticiones (GraphQL siempre usa POST)
function apiRequest(string $url, array $data, string $token = null): array
{
    $curl = curl_init();
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        throw new RuntimeException('cURL error: ' . $error);
    }

    return json_decode($response, true) ?: [];
}

// 1. Login (Sigue siendo REST)
function login(string $email, string $password): array
{
    return apiRequest('http://127.0.0.1:8000/api/login', [
        'email' => $email,
        'password' => $password,
    ]);
}

// 2. Obtener Personas (GraphQL Query)
function getPersonas(string $token): array
{
    $query = 'query { personas { data { id nombres apellidos documento_identidad sexo fecha_nacimiento celular } } }';
    return apiRequest('http://127.0.0.1:8000/graphql', ['query' => $query], $token);
}

// 3. Crear Persona (GraphQL Mutation)
function createPersona(string $token, array $data): array
{
    // Cuidado: Los Enum en GraphQL (como Sexo) no llevan comillas dobles alrededor
    $mutation = 'mutation {
        crearPersona(
            nombres: "'.$data['nombres'].'", 
            apellidos: "'.$data['apellidos'].'", 
            documento_identidad: "'.$data['ci'].'", 
            sexo: '.$data['sexo'].', 
            fecha_nacimiento: "'.$data['fecha'].'", 
            celular: '.$data['celular'].'
        ) {
            id
            nombres
        }
    }';

    return apiRequest('http://127.0.0.1:8000/graphql', ['query' => $mutation], $token);
}


// 4. Actualizar Persona (GraphQL Mutation)
function updatePersona(string $token, array $data): array
{
    $mutation = 'mutation {
        actualizarPersona(
            id: '.$data['id'].',
            nombres: "'.$data['nombres'].'", 
            apellidos: "'.$data['apellidos'].'", 
            documento_identidad: "'.$data['ci'].'", 
            sexo: '.$data['sexo'].', 
            fecha_nacimiento: "'.$data['fecha'].'", 
            celular: '.$data['celular'].'
        ) {
            id
            nombres
        }
    }';
    return apiRequest('http://127.0.0.1:8000/graphql', ['query' => $mutation], $token);
}

// 5. Eliminar Persona (GraphQL Mutation)
function deletePersona(string $token, $id): array
{
    $mutation = 'mutation {
        eliminarPersona(id: '.$id.') {
            id
        }
    }';
    return apiRequest('http://127.0.0.1:8000/graphql', ['query' => $mutation], $token);
}

