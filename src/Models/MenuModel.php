<?php

namespace devuelving\core;

class MenuModel
{
    /**
     * Devuelve la estructura base del menú
     *
     * @return void
     */
    public function getDefaultMenu()
    {
        return  [
            ['type' => 0, 'text' => 'Inicio'],
            ['type' => 2, 'text' => 'Hogar', 'category' => 1],
            ['type' => 1, 'text' => 'Perfumes y cosmética', 'category' => 2],
            ['type' => 1, 'text' => 'Cuidado e higiene personal', 'category' => 3],
            ['type' => 1, 'text' => 'Parafarmacia', 'category' => 4],
            ['type' => 1, 'text' => 'Automóvil', 'category' => 5],
            ['type' => 1, 'text' => 'Nutrición sport', 'category' => 6],
            ['type' => 2, 'text' => 'Dietética natural', 'category' => 7],
            ['type' => 1, 'text' => 'Tecnología', 'category' => 8],
            ['type' => 1, 'text' => 'Carne ecológica', 'category' => 181],
            ['type' => 1, 'text' => 'Infantil', 'category' => 10]
        ];
    }
}
