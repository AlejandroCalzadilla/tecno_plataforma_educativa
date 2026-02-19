<?php


namespace Database\Seeders;

use App\Models\CategoriaNivel;
use Illuminate\Database\Seeder;


class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categorías de ejemplo
        $categoria1 = CategoriaNivel::create([
            'nombre' => 'Matemáticas',
            'id_categoria_padre' => null,
            'estado' => true,
        ]);

        $categoria2 = CategoriaNivel::create([
            'nombre' => 'Álgebra',
            'id_categoria_padre' => $categoria1->id,
            'estado' => true,
        ]);

        $categoria3 = CategoriaNivel::create([
            'nombre' => 'Geometría',
            'id_categoria_padre' => $categoria1->id,
            'estado' => true,
        ]);

        $categoria4 = CategoriaNivel::create([
            'nombre' => 'Ciencias',
            'id_categoria_padre' => null,
            'estado' => true,
        ]);

        $categoria5 = CategoriaNivel::create([
            'nombre' => 'Física',
            'id_categoria_padre' => $categoria4->id,
            'estado' => true,
        ]);

        $categoria6 = CategoriaNivel::create([
            'nombre' => 'Química',
            'id_categoria_padre' => $categoria4->id,
            'estado' => true,
        ]);
    }
}