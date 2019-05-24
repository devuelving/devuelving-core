<?php

namespace devuelving\core;

class MenuModel
{
    /**
     * Devuelve la estructura base del menú
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public static function getDefaultMenu()
    {
        return  [
            ['type' => 0, 'text' => 'Inicio'],
            ['type' => 2, 'text' => 'Hogar', 'category' => 1],
            ['type' => 1, 'text' => 'Perfumes y Cosmética', 'category' => 2],
            ['type' => 1, 'text' => 'Cuidado e Higiene', 'category' => 3],
            ['type' => 1, 'text' => 'Parafarmacia', 'category' => 4],
            ['type' => 1, 'text' => 'Automóvil', 'category' => 5],
            ['type' => 1, 'text' => 'Nutrición Sport', 'category' => 6],
            ['type' => 2, 'text' => 'Dietética Natural', 'category' => 7],
            ['type' => 1, 'text' => 'Tecnología', 'category' => 8],
            ['type' => 1, 'text' => 'Carne Ecológica', 'category' => 181]
        ];
    }
}
