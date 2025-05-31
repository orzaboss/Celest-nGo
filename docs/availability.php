<?php
// api/availability.php - API para horarios y disponibilidad
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
            if (isset($_GET['restaurant_id']) && isset($_GET['action']) && $_GET['action'] === 'status') {
                checkRestaurantStatus($pdo, $_GET['restaurant_id']);
            } elseif (isset($_GET['restaurant_id']) && isset($_GET['action']) && $_GET['action'] === 'schedule') {
                getRestaurantSchedule($pdo, $_GET['restaurant_id']);
            } elseif (isset($_GET['product_id']) && isset($_GET['action']) && $_GET['action'] === 'availability') {
                getProductAvailability($pdo, $_GET['product_id']);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'open_restaurants') {
                getOpenRestaurants($pdo);
            } else {
                getSystemStatus($pdo);
            }
            break;
            
        case 'PUT':
            if (isset($_GET['action']) && $_GET['action'] === 'product_availability') {
                updateProductAvailability($pdo, $input);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'restaurant_schedule') {
                updateRestaurantSchedule($pdo, $input);
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

function checkRestaurantStatus($pdo, $restaurant_id) {
    try {
        $now = new DateTime();
        $day_name = strtolower($now->format('l')); // monday, tuesday, etc.
        $current_time = $now->format('H:i:s');
        
        // Traducir días al español
        $day_translation = [
            'monday' => 'lunes',
            'tuesday' => 'martes', 
            'wednesday' => 'miercoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sabado',
            'sunday' => 'domingo'
        ];
        $dia_espanol = $day_translation[$day_name];
        
        // Obtener información del restaurante y horario
        $query = "SELECT r.*, rh.hora_apertura, rh.hora_cierre, rh.activo as horario_activo
                  FROM restaurantes r
                  LEFT JOIN restaurante_horarios rh ON r.id = rh.restaurante_id 
                  AND rh.dia_semana = ?
                  WHERE r.id = ? AND r.activo = 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$dia_espanol, $restaurant_id]);
        
        if ($restaurant = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $is_open = false;
            $status_message = '';
            
            if (!$restaurant['hora_apertura'] || !$restaurant['horario_activo']) {
                $status_message = 'Cerrado hoy';
            } else {
                $hora_apertura = $restaurant['hora_apertura'];
                $hora_cierre = $restaurant['hora_cierre'];
                
                // Manejar horarios que cruzan medianoche (ej: 18:00 - 02:00)
                if ($hora_cierre < $hora_apertura) {
                    // Horario nocturno
                    $is_open = ($current_time >= $hora_apertura || $current_time <= $hora_cierre);
                } else {
                    // Horario normal
                    $is_open = ($current_time >= $hora_apertura && $current_time <= $hora_cierre);
                }
                
                if ($is_open) {
                    $status_message = 'Abierto';
                } else {
                    $status_message = $current_time < $hora_apertura ? 
                        "Abre a las " . date('H:i', strtotime($hora_apertura)) :
                        "Cerrado - Abre mañana";
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'restaurant_id' => (int)$restaurant_id,
                    'restaurant_name' => $restaurant['nombre'],
                    'is_open' => $is_open,
                    'status_message' => $status_message,
                    'current_time' => $current_time,
                    'today_schedule' => [
                        'opens' => $restaurant['hora_apertura'],
                        'closes' => $restaurant['hora_cierre']
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Restaurante no encontrado']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al verificar estado del restaurante',
            'message' => $e->getMessage()
        ]);
    }
}

function getOpenRestaurants($pdo) {
    try {
        $now = new DateTime();
        $day_name = strtolower($now->format('l'));
        $current_time = $now->format('H:i:s');
        
        $day_translation = [
            'monday' => 'lunes', 'tuesday' => 'martes', 'wednesday' => 'miercoles',
            'thursday' => 'jueves', 'friday' => 'viernes', 'saturday' => 'sabado', 'sunday' => 'domingo'
        ];
        $dia_espanol = $day_translation[$day_name];
        
        $query = "SELECT r.*, rh.hora_apertura, rh.hora_cierre
                  FROM restaurantes r
                  LEFT JOIN restaurante_horarios rh ON r.id = rh.restaurante_id 
                  AND rh.dia_semana = ? AND rh.activo = 1
                  WHERE r.activo = 1
                  ORDER BY r.calificacion DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$dia_espanol]);
        
        $open_restaurants = [];
        $closed_restaurants = [];
        
        while ($restaurant = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $is_open = false;
            
            if ($restaurant['hora_apertura'] && $restaurant['hora_cierre']) {
                $hora_apertura = $restaurant['hora_apertura'];
                $hora_cierre = $restaurant['hora_cierre'];
                
                if ($hora_cierre < $hora_apertura) {
                    $is_open = ($current_time >= $hora_apertura || $current_time <= $hora_cierre);
                } else {
                    $is_open = ($current_time >= $hora_apertura && $current_time <= $hora_cierre);
                }
            }
            
            $restaurant_data = [
                'id' => (int)$restaurant['id'],
                'nombre' => $restaurant['nombre'],
                'descripcion' => $restaurant['descripcion'],
                'calificacion' => (float)$restaurant['calificacion'],
                'tiempo_entrega' => [
                    'min' => (int)$restaurant['tiempo_entrega_min'],
                    'max' => (int)$restaurant['tiempo_entrega_max']
                ],
                'costo_envio' => (float)$restaurant['costo_envio'],
                'is_open' => $is_open,
                'horario_hoy' => [
                    'apertura' => $restaurant['hora_apertura'],
                    'cierre' => $restaurant['hora_cierre']
                ]
            ];
            
            if ($is_open) {
                $open_restaurants[] = $restaurant_data;
            } else {
                $closed_restaurants[] = $restaurant_data;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado de restaurantes obtenido',
            'data' => [
                'open_restaurants' => $open_restaurants,
                'closed_restaurants' => $closed_restaurants,
                'total_open' => count($open_restaurants),
                'total_closed' => count($closed_restaurants),
                'current_time' => $current_time,
                'day' => $dia_espanol
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener restaurantes abiertos',
            'message' => $e->getMessage()
        ]);
    }
}

function getProductAvailability($pdo, $product_id) {
    try {
        $query = "SELECT p.*, pd.disponible, pd.cantidad_estimada, pd.precio_actual, pd.notas,
                         pd.fecha_actualizacion, r.nombre as restaurante_nombre
                  FROM productos p
                  LEFT JOIN producto_disponibilidad pd ON p.id = pd.producto_id
                  LEFT JOIN restaurantes r ON p.restaurante_id = r.id
                  WHERE p.id = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$product_id]);
        
        if ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'product_id' => (int)$product['id'],
                    'nombre' => $product['nombre'],
                    'precio_original' => (float)$product['precio'],
                    'precio_actual' => (float)($product['precio_actual'] ?? $product['precio']),
                    'disponible' => (bool)($product['disponible'] ?? true),
                    'cantidad_estimada' => $product['cantidad_estimada'] ? (int)$product['cantidad_estimada'] : null,
                    'notas' => $product['notas'],
                    'restaurante' => $product['restaurante_nombre'],
                    'ultima_actualizacion' => $product['fecha_actualizacion']
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener disponibilidad del producto',
            'message' => $e->getMessage()
        ]);
    }
}

function updateProductAvailability($pdo, $data) {
    try {
        if (!isset($data['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'product_id es requerido']);
            return;
        }
        
        $updates = [];
        $params = [];
        
        if (isset($data['disponible'])) {
            $updates[] = "disponible = ?";
            $params[] = (bool)$data['disponible'];
        }
        
        if (isset($data['precio_actual'])) {
            $updates[] = "precio_actual = ?";
            $params[] = (float)$data['precio_actual'];
        }
        
        if (isset($data['cantidad_estimada'])) {
            $updates[] = "cantidad_estimada = ?";
            $params[] = $data['cantidad_estimada'] ? (int)$data['cantidad_estimada'] : null;
        }
        
        if (isset($data['notas'])) {
            $updates[] = "notas = ?";
            $params[] = $data['notas'];
        }
        
        if (isset($data['usuario_id'])) {
            $updates[] = "actualizado_por = ?";
            $params[] = (int)$data['usuario_id'];
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No hay datos para actualizar']);
            return;
        }
        
        $params[] = $data['product_id'];
        
        $query = "UPDATE producto_disponibilidad SET " . implode(', ', $updates) . " WHERE producto_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Disponibilidad actualizada correctamente'
            ]);
        } else {
            // Si no existe registro, crear uno nuevo
            $insert_query = "INSERT INTO producto_disponibilidad (producto_id, disponible, precio_actual, cantidad_estimada, notas, actualizado_por) 
                            SELECT id, ?, precio, ?, ?, ? FROM productos WHERE id = ?";
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute([
                $data['disponible'] ?? true,
                $data['cantidad_estimada'] ?? null,
                $data['notas'] ?? '',
                $data['usuario_id'] ?? null,
                $data['product_id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Registro de disponibilidad creado'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al actualizar disponibilidad',
            'message' => $e->getMessage()
        ]);
    }
}

function getSystemStatus($pdo) {
    try {
        // Obtener configuraciones del sistema
        $config_query = "SELECT clave, valor FROM configuracion_sistema WHERE categoria IN ('general', 'horarios', 'sistema')";
        $config_stmt = $pdo->prepare($config_query);
        $config_stmt->execute();
        
        $config = [];
        while ($row = $config_stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }
        
        $sistema_activo = !($config['modo_mantenimiento'] === 'true');
        
        echo json_encode([
            'success' => true,
            'data' => [
                'sistema_activo' => $sistema_activo,
                'app_nombre' => $config['app_nombre'] ?? 'Celestún GO',
                'app_version' => $config['app_version'] ?? '1.0.0',
                'horario_servicio' => [
                    'inicio' => $config['horario_servicio_inicio'] ?? '08:00',
                    'fin' => $config['horario_servicio_fin'] ?? '22:00'
                ],
                'acepta_nuevos_restaurantes' => $config['acepta_nuevos_restaurantes'] === 'true',
                'mensaje_mantenimiento' => $config['mensaje_mantenimiento'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener estado del sistema',
            'message' => $e->getMessage()
        ]);
    }
}
?>