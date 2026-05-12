<?php
 
namespace App\Models;
 
use CodeIgniter\Model;
 
class Usuarios extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id_user';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['usuario', 'password', 'nombre_completo'];

    /**
     * Valida credenciales de usuario (Login simple)
     */
    public function validateCredentials($usuario, $password)
    {
        return $this->where('usuario', $usuario)
                    ->where('password', $password) // Nota: Sin cifrar por requerimiento histórico
                    ->first();
    }
}
