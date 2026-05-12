<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Usuarios;

class Auth extends BaseController
{
    use ResponseTrait;

    public function login()
    {
        try {
            $model = new Usuarios();
            
            $usuario  = $this->request->getVar('usuario');
            $password = $this->request->getVar('password');

            if (empty($usuario) || empty($password)) {
                $json = $this->request->getJSON();
                if ($json) {
                    $usuario  = $json->usuario ?? null;
                    $password = $json->password ?? null;
                }
            }

            if (empty($usuario) || empty($password)) {
                return $this->fail('Usuario y contraseña son requeridos');
            }

            // Delegar validación al Modelo
            $user = $model->validateCredentials($usuario, $password);

            if (!$user) {
                return $this->failUnauthorized('Credenciales incorrectas');
            }

            // Iniciar sesión NATIVA de PHP
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['id_user']    = $user['id_user'];
            $_SESSION['usuario']    = $user['usuario'];
            $_SESSION['isLoggedIn'] = true;

            return $this->respond([
                'status'  => 'success',
                'message' => 'Login exitoso',
                'user'    => [
                    'id'      => $user['id_user'],
                    'usuario' => $user['usuario']
                ]
            ]);

        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function logout()
    {
        $session = \Config\Services::session();
        $session->destroy();
        return $this->respond(['status' => 'success', 'message' => 'Sesión cerrada']);
    }
}
