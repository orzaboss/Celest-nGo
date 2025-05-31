<?php
// api/restaurant_requests.php - API para solicitudes de restaurantes
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=celestun_go;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                getRequest($pdo, $_GET['id']);
            } elseif (isset($_GET['status'])) {
                getRequestsByStatus($pdo, $_GET['status']);
            } else {
                getAllRequests($pdo);
            }
            break;
            
        case 'POST':
            createRequest($pdo, $input);
            break;
            
        case 'PUT':
            if (isset($_GET['action']) && $_GET['action'] === 'review') {
                reviewRequest($pdo, $input);
            } else {
                updateRequest($pdo, $input);
            }
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor',
        'message' => $e->getMessage()
    ]);
}

function createRequest($pdo, $data) {
    try {
        // Validar campos requeridos
        $required = [
            'nombre_comercial', 'nombre_propietario', 'email', 
            'telefono', 'direccion', 'tipo_cocina', 'descripcion'
        ];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => "Campo requerido faltante: $field"
                ]);
                return;
            }
        }
        
        // Verificar si ya existe una solicitud con ese email
        $check_query = "SELECT id, estado FROM restaurante_solicitudes WHERE email = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$data['email']]);
        
        if ($existing = $check_stmt->fetch()) {
            if ($existing['estado'] === 'pendiente' || $existing['estado'] === 'en_revision') {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'error' => 'Ya existe una solicitud pendiente con este email'
                ]);
                return;
            }
        }
        
        // Crear la solicitud
        $query = "INSERT INTO restaurante_solicitudes 
                  (nombre_comercial, nombre_propietario, email, telefono, direccion, 
                   tipo_cocina, descripcion, rfc, licencia_funcionamiento, documentos_legales) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['nombre_comercial'],
            $data['nombre_propietario'],
            $data['email'],
            $data['telefono'],
            $data['direccion'],
            $data['tipo_cocina'],
            $data['descripcion'],
            $data['rfc'] ?? '',
            $data['licencia_funcionamiento'] ?? '',
            json_encode($data['documentos_legales'] ?? [])
        ]);
        
        $request_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Solicitud enviada exitosamente',
            'data' => [
                'request_id' => (int)$request_id,
                'estado' => 'pendiente',
                'mensaje' => 'Tu solicitud ha sido recibida y será revisada en las próximas 24-48 horas.'
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al crear solicitud',
            'message' => $e->getMessage()
        ]);
    }
}

function getAllRequests($pdo) {
    try {
        $query = "SELECT rs.*, u.nombre as revisado_por_nombre 
                  FROM restaurante_solicitudes rs
                  LEFT JOIN usuarios u ON rs.revisado_por = u.id
                  ORDER BY 
                    CASE rs.estado 
                        WHEN 'pendiente' THEN 1 
                        WHEN 'en_revision' THEN 2 
                        WHEN 'aprobado' THEN 3 
                        WHEN 'rechazado' THEN 4 
                    END,
                    rs.fecha_solicitud DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $requests = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $requests[] = [
                'id' => (int)$row['id'],
                'nombre_comercial' => $row['nombre_comercial'],
                'nombre_propietario' => $row['nombre_propietario'],
                'email' => $row['email'],
                'telefono' => $row['telefono'],
                'direccion' => $row['direccion'],
                'tipo_cocina' => $row['tipo_cocina'],
                'descripcion' => $row['descripcion'],
                'estado' => $row['estado'],
                'fecha_solicitud' => $row['fecha_solicitud'],
                'fecha_revision' => $row['fecha_revision'],
                'revisado_por' => $row['revisado_por_nombre'],
                'motivo_rechazo' => $row['motivo_rechazo'],
                'notas_admin' => $row['notas_admin']
            ];
        }
        
        // Contar por estado
        $count_query = "SELECT estado, COUNT(*) as total FROM restaurante_solicitudes GROUP BY estado";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute();
        
        $stats = [];
        while ($count = $count_stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$count['estado']] = (int)$count['total'];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Solicitudes obtenidas correctamente',
            'data' => $requests,
            'stats' => $stats,
            'total' => count($requests)
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener solicitudes',
            'message' => $e->getMessage()
        ]);
    }
}

function getRequestsByStatus($pdo, $status) {
    try {
        $valid_statuses = ['pendiente', 'en_revision', 'aprobado', 'rechazado'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Estado no válido. Estados permitidos: ' . implode(', ', $valid_statuses)
            ]);
            return;
        }
        
        $query = "SELECT rs.*, u.nombre as revisado_por_nombre 
                  FROM restaurante_solicitudes rs
                  LEFT JOIN usuarios u ON rs.revisado_por = u.id
                  WHERE rs.estado = ?
                  ORDER BY rs.fecha_solicitud DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$status]);
        
        $requests = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $requests[] = [
                'id' => (int)$row['id'],
                'nombre_comercial' => $row['nombre_comercial'],
                'nombre_propietario' => $row['nombre_propietario'],
                'email' => $row['email'],
                'telefono' => $row['telefono'],
                'direccion' => $row['direccion'],
                'tipo_cocina' => $row['tipo_cocina'],
                'descripcion' => $row['descripcion'],
                'estado' => $row['estado'],
                'fecha_solicitud' => $row['fecha_solicitud'],
                'fecha_revision' => $row['fecha_revision'],
                'revisado_por' => $row['revisado_por_nombre'],
                'motivo_rechazo' => $row['motivo_rechazo']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Solicitudes con estado '$status' obtenidas",
            'data' => $requests,
            'status_filter' => $status,
            'total' => count($requests)
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener solicitudes por estado',
            'message' => $e->getMessage()
        ]);
    }
}

function getRequest($pdo, $request_id) {
    try {
        $query = "SELECT rs.*, u.nombre as revisado_por_nombre 
                  FROM restaurante_solicitudes rs
                  LEFT JOIN usuarios u ON rs.revisado_por = u.id
                  WHERE rs.id = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$request_id]);
        
        if ($request = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => (int)$request['id'],
                    'nombre_comercial' => $request['nombre_comercial'],
                    'nombre_propietario' => $request['nombre_propietario'],
                    'email' => $request['email'],
                    'telefono' => $request['telefono'],
                    'direccion' => $request['direccion'],
                    'tipo_cocina' => $request['tipo_cocina'],
                    'descripcion' => $request['descripcion'],
                    'rfc' => $request['rfc'],
                    'licencia_funcionamiento' => $request['licencia_funcionamiento'],
                    'documentos_legales' => json_decode($request['documentos_legales'], true),
                    'estado' => $request['estado'],
                    'fecha_solicitud' => $request['fecha_solicitud'],
                    'fecha_revision' => $request['fecha_revision'],
                    'revisado_por' => $request['revisado_por_nombre'],
                    'motivo_rechazo' => $request['motivo_rechazo'],
                    'notas_admin' => $request['notas_admin']
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener solicitud',
            'message' => $e->getMessage()
        ]);
    }
}

function reviewRequest($pdo, $data) {
    try {
        if (!isset($data['request_id']) || !isset($data['decision'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'request_id y decision son requeridos'
            ]);
            return;
        }
        
        if (!in_array($data['decision'], ['aprobar', 'rechazar'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Decision debe ser "aprobar" o "rechazar"'
            ]);
            return;
        }
        
        $pdo->beginTransaction();
        
        if ($data['decision'] === 'aprobar') {
            // Obtener datos de la solicitud
            $get_query = "SELECT * FROM restaurante_solicitudes WHERE id = ? AND estado IN ('pendiente', 'en_revision')";
            $get_stmt = $pdo->prepare($get_query);
            $get_stmt->execute([$data['request_id']]);
            
            if ($request = $get_stmt->fetch(PDO::FETCH_ASSOC)) {
                // Crear el restaurante
                $create_restaurant_query = "INSERT INTO restaurantes 
                    (nombre, descripcion, telefono, direccion, calificacion, tiempo_entrega_min, tiempo_entrega_max, costo_envio, envio_gratis_desde, activo) 
                    VALUES (?, ?, ?, ?, 0, 30, 45, 25, 200, 1)";
                
                $create_stmt = $pdo->prepare($create_restaurant_query);
                $create_stmt->execute([
                    $request['nombre_comercial'],
                    $request['descripcion'],
                    $request['telefono'],
                    $request['direccion']
                ]);
                
                $restaurant_id = $pdo->lastInsertId();
                
                // Crear usuario para el restaurante
                $create_user_query = "INSERT INTO usuarios 
                    (nombre, email, telefono, direccion_principal, rol, restaurante_id, activo, verificado) 
                    VALUES (?, ?, ?, ?, 'restaurante', ?, 1, 1)";
                
                $create_user_stmt = $pdo->prepare($create_user_query);
                $create_user_stmt->execute([
                    $request['nombre_propietario'],
                    $request['email'],
                    $request['telefono'],
                    $request['direccion'],
                    $restaurant_id
                ]);
                
                // Actualizar solicitud como aprobada
                $update_query = "UPDATE restaurante_solicitudes 
                    SET estado = 'aprobado', fecha_revision = NOW(), revisado_por = ?, notas_admin = ? 
                    WHERE id = ?";
                
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->execute([
                    $data['admin_id'] ?? null,
                    $data['notas'] ?? 'Solicitud aprobada automáticamente',
                    $data['request_id']
                ]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Solicitud aprobada exitosamente',
                    'data' => [
                        'restaurant_id' => (int)$restaurant_id,
                        'restaurant_name' => $request['nombre_comercial'],
                        'owner_email' => $request['email']
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                
            } else {
                $pdo->rollback();
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Solicitud no encontrada o ya procesada'
                ]);
            }
            
        } else { // rechazar
            $update_query = "UPDATE restaurante_solicitudes 
                SET estado = 'rechazado', fecha_revision = NOW(), revisado_por = ?, motivo_rechazo = ?, notas_admin = ? 
                WHERE id = ? AND estado IN ('pendiente', 'en_revision')";
            
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                $data['admin_id'] ?? null,
                $data['motivo_rechazo'] ?? 'No especificado',
                $data['notas'] ?? '',
                $data['request_id']
            ]);
            
            if ($update_stmt->rowCount() > 0) {
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Solicitud rechazada',
                    'data' => [
                        'request_id' => (int)$data['request_id'],
                        'motivo' => $data['motivo_rechazo'] ?? 'No especificado'
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                $pdo->rollback();
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Solicitud no encontrada o ya procesada'
                ]);
            }
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al procesar solicitud',
            'message' => $e->getMessage()
        ]);
    }
}

function updateRequest($pdo, $data) {
    try {
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID de solicitud es requerido'
            ]);
            return;
        }
        
        // Solo permitir actualizar solicitudes pendientes
        $check_query = "SELECT estado FROM restaurante_solicitudes WHERE id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$data['id']]);
        
        if ($current = $check_stmt->fetch()) {
            if ($current['estado'] !== 'pendiente') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Solo se pueden modificar solicitudes pendientes'
                ]);
                return;
            }
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Solicitud no encontrada'
            ]);
            return;
        }
        
        $updates = [];
        $params = [];
        
        $allowed_fields = [
            'nombre_comercial', 'nombre_propietario', 'telefono', 
            'direccion', 'tipo_cocina', 'descripcion', 'rfc', 
            'licencia_funcionamiento'
        ];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (isset($data['documentos_legales'])) {
            $updates[] = "documentos_legales = ?";
            $params[] = json_encode($data['documentos_legales']);
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No hay campos para actualizar'
            ]);
            return;
        }
        
        $params[] = $data['id'];
        
        $query = "UPDATE restaurante_solicitudes SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Solicitud actualizada correctamente'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al actualizar solicitud',
            'message' => $e->getMessage()
        ]);
    }
}
?>