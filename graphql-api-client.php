<?php
// funcion para que GrapqQL acepte la conexion
function apiRequest(string $url, array $data = null, string $token = null): array
{
    $curl = curl_init();
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];
    // si el usuario se logeo atrapa su token y va en la cabezera HTTP
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

    // empaquetar las peticiones
    $result = json_decode($response, true);
    return $result ?: [];
}


/** 1. LOGIN (API REST Se comunica con la ruta tradicional de Laravel para obtener el JWT. */
function login(string $email, string $password): array
{
    return apiRequest('http://127.0.0.1:8000/api/login', [
        'email' => $email,
        'password' => $password
    ]);
}

 /* 2. LISTAR PERSONAS (GraphQL Query) pide exactamente los campos que necesita mostrar la tabla. */
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

function deletePersona(string $token, $id): array
{
    $mutation = 'mutation {
        eliminarPersona(id: "'.$id.'") {
            id
        }
    }';
    return apiRequest('http://127.0.0.1:8000/graphql', ['query' => $mutation], $token);
}