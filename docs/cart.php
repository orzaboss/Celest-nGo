<?php
// api/cart.php - API CORREGIDA
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
            if (isset($_GET['user_id'])) {
                getCart($pdo, $_GET['user_id']);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'user_id es requerido'
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
            break;
            
        case 'POST':
            addToCart($pdo, $input);
            break;
            
        case 'PUT':
            updateCartItem($pdo, $input);
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                removeFromCart($pdo, $_GET['id']);
            } elseif (isset($_GET['user_id'])) {
                clearCart($pdo, $_GET['user_id']);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID del item o user_id es requerido'
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
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

function getCart($pdo, $user_id) {
    try {
        $query = "SELECT c.*, p.nombre, p.descripcion, p.precio, p.imagen_url,
                         r.nombre as restaurante_nombre, r.costo_envio, r.envio_gratis_desde,
                         r.id as restaurante_id
                  FROM carrito c
                  JOIN productos p ON c.producto_id = p.id
                  JOIN restaurantes r ON p.restaurante_id = r.id
                  WHERE c.usuario_id = ?
                  ORDER BY c.fecha_agregado DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        
        $cartItems = [];
        $total = 0;
        $restaurante_id = null;
        $costo_envio = 0;
        $envio_gratis_desde = 200;
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $subtotal = $row['precio'] * $row['cantidad'];
            $total += $subtotal;
            
            if ($restaurante_id === null) {
                $restaurante_id = $row['restaurante_id'];
                $costo_envio = $row['costo_envio'];
                $envio_gratis_desde = $row['envio_gratis_desde'];
            }
            
            $cartItems[] = [
                'id' => (int)$row['id'],
                'producto_id' => (int)$row['producto_id'],
                'nombre' => $row['nombre'],
                'descripcion' => $row['descripcion'],
                'precio' => (float)$row['precio'],
                'cantidad' => (int)$row['cantidad'],
                'subtotal' => (float)$subtotal,
                'imagen_url' => $row['imagen_url'],
                'restaurante_nombre' => $row['restaurante_nombre']
            ];
        }
        
        // Calcular costo de envío final
        $costo_envio_final = ($total >= $envio_gratis_desde) ? 0 : $costo_envio;
        
        echo json_encode([
            'success' => true,
            'message' => 'Carrito obtenido correctamente',
            'data' => [
                'items' => $cartItems,
                'resumen' => [
                    'subtotal' => (float)$total,
                    'costo_envio' => (float)$costo_envio_final,
                    'descuento' => 0.0,
                    'total' => (float)($total + $costo_envio_final)
                ],
                'total_items' => count($cartItems),
                'restaurante_id' => $restaurante_id
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener carrito',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

function addToCart($pdo, $data) {
    try {
        // Validar datos requeridos
        if (!isset($data['user_id']) || !isset($data['product_id']) || !isset($data['cantidad'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Datos faltantes: user_id, product_id y cantidad son requeridos'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            return;
        }
        
        // Verificar si el producto ya está en el carrito
        $check_query = "SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$data['user_id'], $data['product_id']]);
        
        if ($existing = $check_stmt->fetch(PDO::FETCH_ASSOC)) {
            // Actualizar cantidad existente
            $new_quantity = $existing['cantidad'] + $data['cantidad'];
            $update_query = "UPDATE carrito SET cantidad = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([$new_quantity, $existing['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cantidad actualizada en el carrito',
                'item_id' => (int)$existing['id']
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            // Agregar nuevo item
            $query = "INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$data['user_id'], $data['product_id'], $data['cantidad']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'item_id' => (int)$pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al agregar al carrito',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

function updateCartItem($pdo, $data) {
    try {
        if (!isset($data['id']) || !isset($data['cantidad'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Datos faltantes: id y cantidad son requeridos'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            return;
        }
        
        if ($data['cantidad'] <= 0) {
            // Si cantidad es 0 o menor, eliminar item
            $query = "DELETE FROM carrito WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$data['id']]);
            $message = 'Item eliminado del carrito';
        } else {
            // Actualizar cantidad
            $query = "UPDATE carrito SET cantidad = ? WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$data['cantidad'], $data['id']]);
            $message = 'Cantidad actualizada';
        }
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Item no encontrado'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al actualizar carrito',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

function removeFromCart($pdo, $id) {
    try {
        $query = "DELETE FROM carrito WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Item eliminado del carrito'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Item no encontrado'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al eliminar item',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

function clearCart($pdo, $user_id) {
    try {
        $query = "DELETE FROM carrito WHERE usuario_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Carrito vaciado',
            'items_eliminados' => $stmt->rowCount()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al vaciar carrito',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
?>