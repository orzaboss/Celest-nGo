<?php
// api/restaurants.php - API CORREGIDA
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Conexión directa a la base de datos
    $pdo = new PDO('mysql:host=localhost;dbname=celestun_go;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener todos los restaurantes
    $query = "SELECT * FROM restaurantes WHERE activo = 1 ORDER BY calificacion DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $restaurants = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Determinar badge
        $badge = null;
        if ($row['costo_envio'] == 0) {
            $badge = 'Envío gratis';
        } elseif ($row['calificacion'] >= 4.8) {
            $badge = 'Popular';
        }
        
        $restaurants[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'telefono' => $row['telefono'],
            'direccion' => $row['direccion'],
            'imagen_url' => $row['imagen_url'],
            'calificacion' => (float)$row['calificacion'],
            'tiempo_entrega' => [
                'min' => (int)$row['tiempo_entrega_min'],
                'max' => (int)$row['tiempo_entrega_max']
            ],
            'costo_envio' => (float)$row['costo_envio'],
            'envio_gratis_desde' => (float)$row['envio_gratis_desde'],
            'badge' => $badge
        ];
    }
    
    // Respuesta exitosa
    $response = [
        'success' => true,
        'message' => 'Restaurantes obtenidos correctamente',
        'data' => $restaurants,
        'total' => count($restaurants),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
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
?>