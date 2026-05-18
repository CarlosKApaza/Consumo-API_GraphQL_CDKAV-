# Cliente Arquitectura Híbrida (REST + GraphQL) con JWT para Laravel

Este proyecto implementa un cliente del lado del servidor (Server-Side Client) construido en PHP nativo, diseñado para consumir un backend de Laravel combinando autenticación REST tradicional con manipulación de datos vía GraphQL.

## Requisitos de Infraestructura

- Servidor Laravel (API) en ejecución en `http://127.0.0.1:8000`.
- Endpoint REST activo en `/api/login` que acepte POST con credenciales `{email, password}` y retorne el respectivo `{token: "jwt_token"}`.
- Endpoint GraphQL activo en `/graphql` configurado a través de Lighthouse para resolver *Queries* y *Mutations*.

## Archivos Centrales

- `graphql-api-client.php`: Motor de comunicación del sistema. Encapsula la lógica de peticiones HTTP (`cURL`) e inyección de cabeceras de autorización.
- `index.php`: Controlador de interfaz y enrutador de acciones. Gestiona el ciclo de vida de la sesión PHP y procesa las peticiones del patrón PRG (Post-Redirect-Get).
- `style.css`: Capa de presentación visual UI/UX.

## Flujo de Ejecución

1. El cliente web se levanta en un puerto independiente (`php -S localhost:8081`) para evitar colisiones con la API.
2. La autenticación se realiza vía REST. El Token JWT obtenido se aísla de forma segura en la memoria del servidor (`$_SESSION['api_token']`).
3. Para la gestión de datos (CRUD), el cliente construye estructuras de texto (GraphQL Schema) y las despacha como un objeto JSON mediante el método POST hacia `/graphql`.

## Ejemplo de Request Autenticado (PHP cURL)

Toda petición segura hacia la API pasa por una capa genérica que inyecta el token en la cabecera HTTP:

```php
function getPersonas(string $token): array
{
    // 1. Se define la estructura de datos exacta que se requiere (Zero Overfetching)
    $query = 'query { personas { data { id nombres celulares } } }';
    
    // 2. Se invoca al motor cURL, apuntando al endpoint único
    return apiRequest('[http://127.0.0.1:8000/graphql](http://127.0.0.1:8000/graphql)', ['query' => $query], $token);
}
```

## Notas Técnicas y Seguridad

Protección de Token: A diferencia de las aplicaciones cliente-servidor basadas en JavaScript (SPAs) que almacenan tokens en localStorage (vulnerables a ataques XSS), este cliente almacena el JWT directamente en la sesión nativa de PHP ($_SESSION), ocultándolo completamente del navegador del usuario final.

Ausencia de CORS: Al realizarse las peticiones HTTP de servidor a servidor (PHP a Laravel) mediante cURL, la arquitectura es inmune a las restricciones de políticas de mismo origen (CORS) que normalmente afectan a los navegadores web.

Consistencia de Métodos: Mientras que el estándar REST requiere alternar verbos HTTP (GET, PUT, DELETE), la integración con GraphQL estandariza toda alteración y lectura de base de datos bajo el método POST.