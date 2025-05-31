<?php
// api/products.php - API CORREGIDA
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Conexión directa a la base de datos
    $pdo = new PDO('mysql:host=localhost;dbname=celestun_go;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Determinar qué productos obtener
    if (isset($_GET['restaurant_id'])) {
        // Productos de un restaurante específico
        $query = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono, 
                         r.nombre as restaurante_nombre, r.calificacion as restaurante_calificacion
                  FROM productos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  LEFT JOIN restaurantes r ON p.restaurante_id = r.id 
                  WHERE p.restaurante_id = ? AND p.disponible = 1 AND r.activo = 1
                  ORDER BY c.nombre, p.nombre";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_GET['restaurant_id']]);
        
        $message = "Productos del restaurante obtenidos";
        $extra_data = ['restaurant_id' => (int)$_GET['restaurant_id']];
        
    } elseif (isset($_GET['category_id'])) {
        // Productos de una categoría específica
        $query = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono, 
                         r.nombre as restaurante_nombre, r.calificacion as restaurante_calificacion
                  FROM productos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  LEFT JOIN restaurantes r ON p.restaurante_id = r.id 
                  WHERE p.categoria_id = ? AND p.disponible = 1 AND r.activo = 1
                  ORDER BY r.nombre, p.nombre";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_GET['category_id']]);
        
        $message = "Productos de la categoría obtenidos";
        $extra_data = ['category_id' => (int)$_GET['category_id']];
        
    } elseif (isset($_GET['id'])) {
        // Un producto específico
        $query = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono, 
                         r.nombre as restaurante_nombre, r.calificacion as restaurante_calificacion
                  FROM productos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  LEFT JOIN restaurantes r ON p.restaurante_id = r.id 
                  WHERE p.id = ? AND p.disponible = 1 AND r.activo = 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_GET['id']]);
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = formatProduct($row);
            echo json_encode([
                'success' => true,
                'message' => 'Producto obtenido correctamente',
                'data' => $product,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Producto no encontrado'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
        
    } else {
        // Todos los productos
        $query = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono, 
                         r.nombre as restaurante_nombre, r.calificacion as restaurante_calificacion
                  FROM productos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  LEFT JOIN restaurantes r ON p.restaurante_id = r.id 
                  WHERE p.disponible = 1 AND r.activo = 1
                  ORDER BY r.nombre, c.nombre, p.nombre";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $message = "Todos los productos obtenidos";
        $extra_data = [];
    }
    
    // Procesar resultados
    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = formatProduct($row);
    }
    
    // Respuesta exitosa
    $response = [
        'success' => true,
        'message' => $message,
        'data' => $products,
        'total' => count($products),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Agregar datos extra si existen
    if (!empty($extra_data)) {
        $response = array_merge($response, $extra_data);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function formatProduct($row) {
    return [
        'id' => (int)$row['id'],
        'nombre' => $row['nombre'],
        'descripcion' => $row['descripcion'],
        'precio' => (float)$row['precio'],
        'imagen_url' => $row['imagen_url'],
        'disponible' => (bool)$row['disponible'],
        'categoria' => [
            'id' => (int)$row['categoria_id'],
            'nombre' => $row['categoria_nombre'],
            'icono' => $row['categoria_icono']
        ],
        'restaurante' => [
            'id' => (int)$row['restaurante_id'],
            'nombre' => $row['restaurante_nombre'],
            'calificacion' => $row['restaurante_calificacion'] ? (float)$row['restaurante_calificacion'] : null
        ]
    ];
}
?>