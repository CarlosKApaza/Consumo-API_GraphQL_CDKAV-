<?php

/**
 * Función Genérica para peticiones HTTP con cURL
 * En esta arquitectura Híbrida, TANTO el Login (REST) como GraphQL usan el método POST.
 */
function apiRequest(string $url, array $data = null, string $token = null): array
{
    $curl = curl_init();
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    // Si hay un token, lo inyectamos en la cabecera
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST', // Siempre POST para REST-Login y GraphQL
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        throw new RuntimeException('Error de cURL: ' . $error);
    }

    $result = json_decode($response, true);
    return $result ?: [];
}

/**
 * 1. LOGIN (API REST)
 * Se comunica con la ruta tradicional de Laravel para obtener el JWT.
 */
function login(string $email, string $password): array
{
    return apiRequest('http://127.0.0.1:8000/api/login', [
        'email' => $email,
        'password' => $password
    ]);
}

/**
 * 2. LISTAR PERSONAS (GraphQL Query)
 * Pide exactamente los campos que necesita mostrar la tabla.
 */
function getPersonas(string $token): array
{
    $query = 'query {
        personas {
            data {
                id
                nombres
                apellidos
                documento_identidad
                sexo
                fecha_nacimiento
                celular
            }
        }
    }';
    return apiRequest('http://127.0.0.1:8000/graphql', ['query' => $query], $token);
}

/**
 * 3. CREAR PERSONA (GraphQL Mutation)
 */
function createPersona(string $token, array $data): array
{
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

/**
 * 4. ACTUALIZAR PERSONA (GraphQL Mutation)
 */
function updatePersona(string $token, array $data): array
{
    $mutation = 'mutation {
        actualizarPersona(
            id: "'.$data['id'].'", 
            nombres: "'.$data['nombres'].'", 
            apellidos: "'.$data['apellidos'].'", 
            documento_identidad: "'.$data['ci'].'", 
            sexo: '.$data['sexo'].', 
            fecha_nacimiento: "'.$data['fecha'].'", 
            celular: '.$data['celular'].'
        ) {
            id
        }
    }';
    return apiRequest('http://127.0.0.1:8000/graphql', ['query' => $mutation], $token);
}

/**
 * 5. ELIMINAR PERSONA (GraphQL Mutation)
 * Utiliza comillas en el ID para respetar la validación @whereKey de Lighthouse.
 */
function deletePersona(string $token, $id): array
{
    $mutation = 'mutation {
        eliminarPersona(id: "'.$id.'") {
            id
        }
    }';
    return apiRequest('http://127.0.0.1:8000/graphql', ['query' => $mutation], $token);
}