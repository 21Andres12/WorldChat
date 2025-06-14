<?php
namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

use PDO;

class Usuario
{
    protected $container;

    public function __construct(ContainerInterface $c)
    {
        $this->container = $c;
    }

    public function read(Request $request, Response $response, $args)
    {
        $sql = "SELECT * FROM Usuario ";

        $con = $this->container->get('base_datos');
        $query = $con->prepare($sql);

        $query->execute();

        $res = $query->fetchAll(PDO::FETCH_ASSOC);

        // Convertir campos varbinary a base64, asumiendo que "contrasenna" y "foto_perfil" son binarios
        foreach ($res as &$row) {
            if (isset($row['contrasenna']) && !is_null($row['contrasenna'])) {
                $row['contrasenna'] = base64_encode($row['contrasenna']);
            }
        }
        unset($row); // buena práctica después de referencia foreach

        $status = count($res) > 0 ? 200 : 204;

        $query = null;
        $con = null;

        $response->getBody()->write(json_encode($res));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }



    public function create(Request $request, Response $response, $args)
    {
        $body = json_decode($request->getBody(), true);

        // Convertir cadenas vacías a null
        foreach ($body as $k => $v) {
            if (is_string($v) && trim($v) === "") {
                $body[$k] = null;
            }
        }

        $sql = "EXEC sp_CrearUsuario @nombre_usuario = :nombre_usuario, @nombre_completo = :nombre_completo, @telefono = :telefono, @correo = :correo, @contrasenna = :contrasenna, @foto_perfil = :foto_perfil, @estado = :estado";

        $con = $this->container->get('base_datos');
        $con->beginTransaction();
        $query = $con->prepare($sql);

        $query->bindValue(':nombre_usuario', $body['nombre_usuario'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':nombre_completo', $body['nombre_completo'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':telefono', $body['telefono'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':correo', $body['correo'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':contrasenna', $body['contrasenna'], PDO::PARAM_STR);
        $query->bindValue(':foto_perfil', $body['foto_perfil'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':estado', $body['estado'] ?? 'Activo', PDO::PARAM_STR);

        try {
            $query->execute();
            $con->commit();
            $status = 201;
        } catch (\PDOException $e) {
            $status = 500;
            $con->rollBack();
        }

        $query = null;
        $con = null;

        return $response->withStatus($status);
    }

    public function update(Request $request, Response $response, $args)
    {
        $body = json_decode($request->getBody(), true);

        // Convertir cadenas vacías a null
        foreach ($body as $k => $v) {
            if (is_string($v) && trim($v) === "") {
                $body[$k] = null;
            }
        }

        $sql = "EXEC sp_ModificarUsuario @id_usuario = :id_usuario, @nombre_usuario = :nombre_usuario, @nombre_completo = :nombre_completo, @telefono = :telefono, @correo = :correo, @contrasenna = :contrasenna, @foto_perfil = :foto_perfil, @estado = :estado";

        $con = $this->container->get('base_datos');
        $con->beginTransaction();
        $query = $con->prepare($sql);

        $query->bindValue(':id_usuario', filter_var($args['id'], FILTER_SANITIZE_SPECIAL_CHARS) ?? null, PDO::PARAM_INT);
        $query->bindValue(':nombre_usuario', $body['nombre_usuario'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':nombre_completo', $body['nombre_completo'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':telefono', $body['telefono'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':correo', $body['correo'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':contrasenna', $body['contrasenna'], PDO::PARAM_STR);
        $query->bindValue(':foto_perfil', $body['foto_perfil'] ?? null, PDO::PARAM_STR);
        $query->bindValue(':estado', $body['estado'] ?? 'Activo', PDO::PARAM_STR);

        try {
            $query->execute();
            $con->commit();
            $status = 201;
        } catch (\PDOException $e) {
            var_dump($e->getMessage());
            die();
            $status = 500;
            $con->rollBack();
        }

        $query = null;
        $con = null;

        return $response->withStatus($status);
    }

}