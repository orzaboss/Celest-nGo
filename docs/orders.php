<?php
// api/orders.php - API CORREGIDA FINAL
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Conexión directa a la base de datos
    $pdo = new PDO('mysql:host=localhost;dbname=celestun_go;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                getOrder($pdo, $_GET['id']);
            } elseif (isset($_GET['user_id'])) {
                getUserOrders($pdo, $_GET['user_id']);
            } else {
                getAllOrders($pdo);
            }
            break;
            
        case 'POST':
            createOrder($pdo, $input);
            break;
            
        case 'PUT':
            updateOrderStatus($pdo, $input);
            break;
        
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Método no permitido'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function getAllOrders($pdo) {
    try {
        $query = "SELECT p.*, r.nombre as restaurante_nombre, u.nombre as usuario_nombre
                  FROM pedidos p
                  JOIN restaurantes r ON p.restaurante_id = r.id
                  LEFT JOIN usuarios u ON p.usuario_id = u.id
                  ORDER BY p.fecha_pedido DESC
                  LIMIT 50";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = [
                'id' => (int)$row['id'],
                'usuario_nombre' => $row['usuario_nombre'],
                'restaurante_nombre' => $row['restaurante_nombre'],
                'total' => (float)$row['total'],
                'estado' => $row['estado'],
                'fecha_pedido' => $row['fecha_pedido'],
                'direccion_entrega' => $row['direccion_entrega'],
                'metodo_pago' => $row['metodo_pago']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Todos los pedidos obtenidos',
            'data' => $orders,
            'total' => count($orders),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener pedidos',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

function getUserOrders($pdo, $user_id) {
    try {
        $query = "SELECT p.*, r.nombre as restaurante_nombre 
                  FROM pedidos p
                  JOIN restaurantes r ON p.restaurante_id = r.id
                  WHERE p.usuario_id = ?
                  ORDER BY p.fecha_pedido DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = [
                'id' => (int)$row['id'],
                'restaurante_nombre' => $row['restaurante_nombre'],
                'total' => (float)$row['total'],
                'estado' => $row['estado'],
                'fecha_pedido' => $row['fecha_pedido'],
                'direccion_entrega' => $row['direccion_entrega'],
                'metodo_pago' => $row['metodo_pago']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedidos del usuario obtenidos',
            'data' => $orders,
            'total' => count($orders),
            'user_id' => (int)$user_id,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener pedidos del usuario',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

function getOrder($pdo, $order_id) {
    try {
        $query = "SELECT p.*, r.nombre as restaurante_nombre, r.telefono as restaurante_telefono,
                         u.nombre as usuario_nombre, u.email as usuario_email
                  FROM pedidos p
                  JOIN restaurantes r ON p.restaurante_id = r.id
                  LEFT JOIN usuarios u ON p.usuario_id = u.id
                  WHERE p.id = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$order_id]);
        
        if ($order = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Obtener detalles del pedido
            $details_query = "SELECT pd.*, pr.nombre as producto_nombre, pr.descripcion 
                             FROM pedido_detalles pd
                             JOIN productos pr ON pd.producto_id = pr.id
                             WHERE pd.pedido_id = ?";
            
            $details_stmt = $pdo->prepare($details_query);
            $details_stmt->execute([$order_id]);
            
            $items = [];
            while ($detail = $details_stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = [
                    'producto_id' => (int)$detail['producto_id'],
                    'nombre' => $detail['producto_nombre'],
                    'descripcion' => $detail['descripcion'],
                    'cantidad' => (int)$detail['cantidad'],
                    'precio_unitario' => (float)$detail['precio_unitario'],
                    'subtotal' => (float)$detail['subtotal']
                ];
            }
            
            $order_data = [
                'id' => (int)$order['id'],
                'usuario' => [
                    'nombre' => $order['usuario_nombre'],
                    'email' => $order['usuario_email']
                ],
                'restaurante' => [
                    'nombre' => $order['restaurante_nombre'],
                    'telefono' => $order['restaurante_telefono']
                ],
                'total' => (float)$order['total'],
                'costo_envio' => (float)$order['costo_envio'],
                'direccion_entrega' => $order['direccion_entrega'],
                'telefono_contacto' => $order['telefono_contacto'],
                'estado' => $order['estado'],
                'metodo_pago' => $order['metodo_pago'],
                'notas' => $order['notas'],
                'fecha_pedido' => $order['fecha_pedido'],
                'fecha_entrega' => $order['fecha_entrega'],
                'items' => $items
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Pedido obtenido correctamente',
                'data' => $order_data,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Pedido no encontrado'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener pedido',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

function createOrder($pdo, $data) {
    try {
        // Validar datos requeridos
        $required = ['user_id', 'direccion_entrega', 'telefono_contacto', 'items'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => "Campo requerido faltante: $field"
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                return;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Función createOrder disponible - implementar según necesidades',
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al crear pedido',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

function updateOrderStatus($pdo, $data) {
    try {
        if (!isset($data['id']) || !isset($data['estado'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Datos faltantes: id y estado son requeridos'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            return;
        }
        
        $valid_statuses = ['pendiente', 'confirmado', 'preparando', 'en_camino', 'entregado', 'cancelado'];
        if (!in_array($data['estado'], $valid_statuses)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Estado no válido. Estados permitidos: ' . implode(', ', $valid_statuses)
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            return;
        }
        
        $query = "UPDATE pedidos SET estado = ?";
        $params = [$data['estado']];
        
        // Si se marca como entregado, actualizar fecha de entrega
        if ($data['estado'] === 'entregado') {
            $query .= ", fecha_entrega = NOW()";
        }
        
        $query .= " WHERE id = ?";
        $params[] = $data['id'];
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Estado del pedido actualizado',
                'nuevo_estado' => $data['estado']
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Pedido no encontrado'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al actualizar estado',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
?>