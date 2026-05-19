<?php
session_start();
require_once __DIR__ . '/graphql-api-client.php';

$message = '';
$error = '';
$personas = [];
$token = $_SESSION['api_token'] ?? null;
$action = $_POST['action'] ?? null;

try {
    // login rest
    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $loginData = login($email, $password);
        if (isset($loginData['token'])) {
            $_SESSION['api_token'] = $loginData['token'];
            $token = $_SESSION['api_token'];
            $message = 'Login exitoso. Token guardado en sesión.';
        } else {
            throw new RuntimeException("Error del servidor: " . json_encode($loginData));
        }
    }

    // Cerrar session
    if ($action === 'logout') {
        session_destroy();
        header("Location: index.php");
        exit;
    }

    // Crear Persona (GraphQL)
    if ($token && $action === 'create_persona' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $created = createPersona($token, [
            'nombres' => trim($_POST['nombres']),
            'apellidos' => trim($_POST['apellidos']),
            'ci' => trim($_POST['ci']),
            'sexo' => $_POST['sexo'],
            'fecha' => $_POST['fecha'],
            'celular' => intval($_POST['celular'])
        ]);
        
        if (isset($created['errors'])) {
            throw new RuntimeException("Error GraphQL: " . $created['errors'][0]['message']);
        }
        $message = 'Persona registrada exitosamente.';
    }

    // Actualizar Persona (GraphQL)
    if ($token && $action === 'update_persona' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $updated = updatePersona($token, [
            'id' => trim($_POST['id']),
            'nombres' => trim($_POST['nombres']),
            'apellidos' => trim($_POST['apellidos']),
            'ci' => trim($_POST['ci']),
            'sexo' => $_POST['sexo'],
            'fecha' => $_POST['fecha'],
            'celular' => intval($_POST['celular'])
        ]);
        
        if (isset($updated['errors'])) {
            throw new RuntimeException("Error GraphQL: " . $updated['errors'][0]['message']);
        }
        $message = 'Persona actualizada exitosamente.';
    }

    // Eliminar Persona (GraphQL)
    if ($token && $action === 'delete_persona' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $deleted = deletePersona($token, trim($_POST['id']));
        
        if (isset($deleted['errors'])) {
            throw new RuntimeException("Error GraphQL: " . $deleted['errors'][0]['message']);
        }
        $message = 'Persona eliminada exitosamente.';
    }

    // Cargar Tabla (GraphQL)
    if ($token) {
        $respuesta = getPersonas($token);
        $personas = $respuesta['data']['personas']['data'] ?? [];
    }

} catch (Exception $ex) {
    $error = $ex->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente Híbrido: REST + GraphQL</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h2>Sistema de Gestión de Agenda</h2>
    <p>Consumo de API Híbrida (REST + GraphQL)</p>
</header>

<div class="container">
    <?php if ($error): ?> <div class="alert alert-error"><?= htmlspecialchars($error) ?></div> <?php endif; ?>
    <?php if ($message): ?> <div class="alert alert-success"><?= htmlspecialchars($message) ?></div> <?php endif; ?>

    <?php if (!$token): ?>
        <div class="login-box">
            <h3>Iniciar Sesión (API REST)</h3>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" placeholder="ejemplo@correo.com" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit">Ingresar al Sistema</button>
            </form>
        </div>

    <?php else: ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
        <div style="clear:both;"></div>

        <div class="grid-2">
            <div class="panel-form">
                <h3>Registrar Nueva Persona</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_persona">
                    <div class="form-group"><label>Nombres</label><input type="text" name="nombres" required></div>
                    <div class="form-group"><label>Apellidos</label><input type="text" name="apellidos" required></div>
                    <div class="form-group"><label>Documento Identidad (CI)</label><input type="text" name="ci" required></div>
                    <div class="form-group">
                        <label>Sexo</label>
                        <select name="sexo" required>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Fecha Nacimiento</label><input type="date" name="fecha" required></div>
                    <div class="form-group"><label>Celular</label><input type="number" name="celular" required></div>
                    <button type="submit">Crear Persona</button>
                </form>
            </div>

            <div class="panel-form">
                <h3>Actualizar Persona</h3>
                <form method="POST" id="formUpdate">
                    <input type="hidden" name="action" value="update_persona">
                    <div class="form-group"><label>ID (Solo lectura)</label><input type="text" id="upd_id" name="id" readonly required></div>
                    <div class="form-group"><label>Nombres</label><input type="text" id="upd_nombres" name="nombres" required></div>
                    <div class="form-group"><label>Apellidos</label><input type="text" id="upd_apellidos" name="apellidos" required></div>
                    <div class="form-group"><label>Documento Identidad (CI)</label><input type="text" id="upd_ci" name="ci" required></div>
                    <div class="form-group">
                        <label>Sexo</label>
                        <select id="upd_sexo" name="sexo" required>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Fecha Nacimiento</label><input type="date" id="upd_fecha" name="fecha" required></div>
                    <div class="form-group"><label>Celular</label><input type="number" id="upd_celular" name="celular" required></div>
                    <button type="submit">Actualizar Datos</button>
                </form>
            </div>
        </div>

        <hr>

        <h2 style="text-align: center;">Listado General de Registros</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Nombres</th><th>Apellidos</th><th>CI</th><th>Sexo</th><th>Celular</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($personas)): ?>
                    <?php foreach ($personas as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['id']) ?></td>
                            <td><?= htmlspecialchars($p['nombres']) ?></td>
                            <td><?= htmlspecialchars($p['apellidos']) ?></td>
                            <td><?= htmlspecialchars($p['documento_identidad']) ?></td>
                            <td><?= htmlspecialchars($p['sexo']) ?></td>
                            <td><?= htmlspecialchars($p['celular']) ?></td>
                            <td>
                                <div class="actions-wrapper">
                                    <button type="button" class="btn-edit" onclick='cargarDatos(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                                        Editar
                                    </button>
                                    
                                    <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('¿Seguro que deseas eliminar a esta persona?');">
                                        <input type="hidden" name="action" value="delete_persona">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn-delete">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No hay datos disponibles en el servidor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function cargarDatos(persona) {
    document.getElementById('upd_id').value = persona.id;
    document.getElementById('upd_nombres').value = persona.nombres;
    document.getElementById('upd_apellidos').value = persona.apellidos;
    document.getElementById('upd_ci').value = persona.documento_identidad;
    document.getElementById('upd_sexo').value = persona.sexo;
    document.getElementById('upd_fecha').value = persona.fecha_nacimiento;
    document.getElementById('upd_celular').value = persona.celular;
    
    document.getElementById('formUpdate').scrollIntoView({ behavior: 'smooth' });
}
</script>

<footer>
    <p>&copy; 2026 - Sistemas Distribuidos </p>
    <p style="font-size: 0.75rem; opacity: 0.7;">Universidad Mayor de San Francisco Xavier de Chuquisaca</p>
</footer>

</body>
</html>