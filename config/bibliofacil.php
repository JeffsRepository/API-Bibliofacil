<?php

return [
    // Prazo padrão sugerido para devolução (dias)
    'prazo_emprestimo_dias' => (int) env('BIBLIOFACIL_PRAZO_EMPRESTIMO_DIAS', 14),

    // Bloqueia novo empréstimo se o leitor tiver algum empréstimo em atraso
    'bloquear_cliente_com_atraso' => (bool) env('BIBLIOFACIL_BLOQUEAR_COM_ATRASO', true),

    // Máximo de empréstimos em aberto por leitor (0 = sem limite)
    'limite_emprestimos_por_cliente' => (int) env('BIBLIOFACIL_LIMITE_EMPRESTIMOS', 3),
];
