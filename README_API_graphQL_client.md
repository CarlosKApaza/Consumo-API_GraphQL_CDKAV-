# Cliente PHP Híbrido para la API de Agenda (REST + GraphQL)

Este documento explica cómo conectar un cliente PHP con una API Híbrida y realizar llamadas como un consumidor, utilizando REST para la autenticación y GraphQL para la gestión de datos de personas.

## Archivos clave

- `graphql-api-client.php`: define funciones PHP que usan cURL para comunicarse con la API de forma centralizada.
- `index.php`: interfaz web cliente en PHP que utiliza `graphql-api-client.php` para autenticar usuarios y realizar operaciones CRUD sobre personas.

## Concepto

El cliente PHP actúa como un consumidor de la API Híbrida. En lugar de mapear cada operación a un método HTTP distinto, se concentra la comunicación en dos endpoints fijos mediante el método POST:

- `POST /api/login` -> Autenticación y obtención de credenciales (REST)
- `POST /graphql` -> Consultas de lectura (`query`) y alteraciones (`mutation`) de personas (GraphQL)

## Cómo funciona la conexión

1. El cliente envía todas las peticiones HTTP mediante cURL utilizando exclusivamente el método POST.
2. Se construyen e inyectan las cabeceras obligatorias: `Accept: application/json` y `Content-Type: application/json`.
3. Si el endpoint de GraphQL requiere autenticación, se adjunta el token JWT bajo el formato `Authorization: Bearer {token}`.
4. La API procesa el payload JSON y retorna la respuesta estructurada.
5. El cliente decodifica el JSON de respuesta y devuelve un array asociativo nativo de PHP.

## ¿Qué es cURL?

`cURL` es una extensión de PHP que permite realizar peticiones de red desde el entorno del servidor hacia endpoints externos. En este proyecto, `cURL` se configura para enviar cadenas de texto estructuradas con la sintaxis de GraphQL hacia el servidor de Laravel y capturar las respuestas del backend.

## ¿Qué hace `apiRequest()`?

La función `apiRequest()` es una capa de abstracción genérica que:

- Inicializa la sesión cURL.
- Adjunta las cabeceras requeridas e inyecta dinámicamente el Bearer Token si se encuentra disponible.
- Configura el transporte de datos fijando el verbo HTTP a `POST`.
- Codifica los parámetros o esquemas de GraphQL a formato JSON para el cuerpo de la petición.
- Ejecuta la llamada hacia el servidor, cierra la sesión cURL y controla posibles excepciones de conectividad.

## Ejemplo de llamada cliente en PHP

```php
function apiRequest(string $url, array $data = null, string $token = null): array
{
    $curl = curl_init();
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
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
```

## Ejemplo de login

```php
function login(string $email, string $password): array
{
    return apiRequest('[http://127.0.0.1:8000/api/login](http://127.0.0.1:8000/api/login)', [
        'email' => $email,
        'password' => $password
    ]);
}
```

El método `login()` devuelve los datos de respuesta de la API REST, incluyendo la firma del token de acceso:

```php
$loginData = login('test@example.com', 'password');
$token = $loginData['token'];
```

## Ejemplo de llamada autenticada

Para leer o descargar el listado de registros, se realiza una petición estructurada de tipo `query` solicitando de forma explícita los campos que requiere pintar la interfaz:

```php
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
    return apiRequest('[http://127.0.0.1:8000/graphql](http://127.0.0.1:8000/graphql)', ['query' => $query], $token);
}
```

## Estructura de datos de Persona

Al realizar modificaciones en la base de datos (crear, actualizar o eliminar), se despacha un bloque de tipo `mutation` concatenando las siguientes variables:

- `nombres` (string)
- `apellidos` (string)
- `documento_identidad` / `ci` (string)
- `sexo` (Enum sin comillas: Masculino / Femenino)
- `fecha_nacimiento` / `fecha` (date)
- `celular` (int)

Ejemplo de mutación estructurada para inserción de datos:

```php
createPersona($token, [
    'nombres' => 'Carlos Daniel',
    'apellidos' => 'Apaza Villca',
    'ci' => '12345678',
    'sexo' => 'Masculino',
    'fecha' => '2003-03-25',
    'celular' => 70000000
]);
```

## Recomposición del flujo

1. El usuario introduce el correo electrónico y su contraseña en el formulario de acceso de la interfaz.
2. Se procesa la función de autenticación `login()` enviando un POST síncrono al endpoint REST del servidor Laravel.
3. Al recibir una respuesta exitosa, el token JWT devuelto se almacena localmente dentro de la sesión global de PHP (`$_SESSION['api_token']`).
4. Para cargar la vista de datos o ejecutar el CRUD, el archivo `index.php` construye las estructuras de strings requeridas por GraphQL (`query` o `mutation`).
5. Se invoca a `apiRequest()` enviando el payload empaquetado hacia la ruta única `/graphql`, adjuntando las cabeceras Bearer correspondientes.
6. Se procesa la respuesta JSON anidada devuelta por el motor del servidor y se renderiza en la tabla de registros.

## Uso rápido

- Toda la lógica operativa del cliente HTTP se gestiona de forma centralizada en `graphql-api-client.php`.
- La orquestación visual de los formularios y las respuestas se centraliza en `index.php`.
- Ajustar la dirección `http://127.0.0.1:8000` si los servicios o puertos locales del servidor de Laravel se despliegan en configuraciones distintas.

## Nota

Este documento detalla los flujos lógicos de conexión y consumo de endpoints de la infraestructura híbrida del backend, abstrayéndose del comportamiento estético de la hoja de estilos o el marcado HTML de la interfaz de usuario.