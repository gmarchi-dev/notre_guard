<?php

/*
|--------------------------------------------------------------------------
| Segurança do vigilante
|--------------------------------------------------------------------------
*/

return [
    /*
     * Minutos de silêncio numa ronda em andamento antes de alertar a
     * supervisão. Curto demais gera ruído e a supervisão para de olhar; longo
     * demais atrasa o socorro.
     *
     * O padrão de 30 minutos parte da duração prevista das rondas cadastradas
     * (30 a 40 min para o perímetro completo): mais que isso sem nenhuma
     * leitura significa que a ronda travou.
     *
     * Ajustar depois do piloto, com dado real de quanto tempo leva entre pontos.
     */
    'inactivity_minutes' => (int) env('SAFETY_INACTIVITY_MINUTES', 30),
];
