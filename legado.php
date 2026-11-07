<?php
// legado.php — "funciona há 8 anos, ninguém mexe"
// Uso: php legado.php gerar_csv | gerar_html_e_enviar | arquivar_csv

$vendas = [
    ['id' => 1, 'cliente' => 'Ana',   'categoria' => 'bebidas', 'valor' => 120.50, 'data' => '2026-09-28'],
    ['id' => 2, 'cliente' => 'Bruno', 'categoria' => 'comidas', 'valor' => 89.90,  'data' => '2026-10-02'],
    ['id' => 3, 'cliente' => 'Carla', 'categoria' => 'bebidas', 'valor' => 45.00,  'data' => '2026-10-05'],
    ['id' => 4, 'cliente' => 'Diego', 'categoria' => 'comidas', 'valor' => 210.00, 'data' => '2026-10-09'],
    ['id' => 5, 'cliente' => 'Elisa', 'categoria' => 'bebidas', 'valor' => 75.30,  'data' => '2026-10-12'],
];

function gerarRelatorio($vendas, $formato, $dataIni, $dataFim, $categoria, $ordenarPor,
                        $desc, $titulo, $mostrarTotal, $arquivo, $enviarEmail, $emailDestino)
{
    // 1) filtrar
    $dados = [];
    foreach ($vendas as $v) {
        if ($dataIni && $v['data'] < $dataIni) continue;
        if ($dataFim && $v['data'] > $dataFim) continue;
        if ($categoria && $v['categoria'] != $categoria) continue;
        $dados[] = $v;
    }

    // 2) ordenar
    if ($ordenarPor) {
        usort($dados, function ($a, $b) use ($ordenarPor, $desc) {
            $r = $a[$ordenarPor] <=> $b[$ordenarPor];
            return $desc ? -$r : $r;
        });
    }

    // 3) formatar (um bloco copiado e colado por formato)
    $saida = '';
    if ($formato == 'csv') {
        $saida .= "id;cliente;categoria;valor;data\n";
        foreach ($dados as $v) {
            $saida .= implode(';', $v) . "\n";
        }
        if ($mostrarTotal) {
            $saida .= "TOTAL;;;" . array_sum(array_column($dados, 'valor')) . ";\n";
        }
    } elseif ($formato == 'html') {
        $saida .= "<h1>$titulo</h1>\n<table>\n";
        $saida .= "<tr><th>id</th><th>cliente</th><th>categoria</th><th>valor</th><th>data</th></tr>\n";
        foreach ($dados as $v) {
            $saida .= "<tr><td>{$v['id']}</td><td>{$v['cliente']}</td><td>{$v['categoria']}</td>"
                    . "<td>{$v['valor']}</td><td>{$v['data']}</td></tr>\n";
        }
        if ($mostrarTotal) {
            $saida .= "<tr><td colspan='3'>TOTAL</td><td>" . array_sum(array_column($dados, 'valor')) . "</td><td></td></tr>\n";
        }
        $saida .= "</table>\n";
    } elseif ($formato == 'json') {
        $obj = ['titulo' => $titulo, 'itens' => $dados];
        if ($mostrarTotal) {
            $obj['total'] = array_sum(array_column($dados, 'valor'));
        }
        $saida = json_encode($obj, JSON_PRETTY_PRINT) . "\n";
    }
    // formato desconhecido? segue em silêncio, saída vazia...

    // 4) salvar e enviar
    if ($arquivo) {
        file_put_contents($arquivo, $saida);
    }
    if ($enviarEmail) {
        echo "[EMAIL] enviando relatório para $emailDestino\n";
    }

    return $saida;
}

// "controller" do sistema
@mkdir('out');
@mkdir('arquivo');
$acao = $argv[1] ?? 'gerar_csv';

switch ($acao) {
    case 'gerar_csv':
        echo gerarRelatorio($vendas, 'csv', '2026-10-01', null, null, 'valor', true,
                            'Vendas de outubro', true, 'out/vendas.csv', false, null);
        break;

    case 'gerar_html_e_enviar':
        echo gerarRelatorio($vendas, 'html', null, null, 'bebidas', null, false,
                            'Bebidas', true, 'out/bebidas.html', true, 'gerente@loja.com');
        break;

    case 'arquivar_csv':
        if (file_exists('out/vendas.csv')) {
            rename('out/vendas.csv', 'arquivo/vendas.csv');
            echo "Arquivado.\n";
        }
        break;

    default:
        echo "Ação desconhecida.\n";
}