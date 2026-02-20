<?php

namespace Database\Seeders;

use App\Models\CategoriaNivel;
use App\Models\Servicio;
use Illuminate\Database\Seeder;


class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Aquí puedes crear servicios de ejemplo usando el modelo Servicio
        // Ejemplo:
         $categorias = CategoriaNivel::all();
        if ($categorias->isEmpty()) {
            $this->command->info('No hay categorías disponibles. Por favor, ejecuta primero el seeder de categorías.');
            return;
        }

        for ($i = 0; $i < count($categorias); $i++) {
            $categoria = $categorias[$i];
            Servicio::create([
                'id_categoria' => $categoria->id,
                'nombre' => "Servicio de Tutoría en {$categoria->nombre}",
                'descripcion' => "Tutoría personalizada para estudiantes de {$categoria->nombre}.",
                //'costo_base' => random_int(100, 500), // Costo aleatorio entre 100 y 500
                'modalidad' => ['VIRTUAL', 'PRESENCIAL', 'HIBRIDO'][array_rand(['VIRTUAL', 'PRESENCIAL', 'HIBRIDO'])], // Modalidad aleatoria
                'estado_activo' => true,
                  ]);
        }
       
    
        }
        
}  
