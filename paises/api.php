
<?php
header('Content-Type: application/json; charset=utf-8');

function responder($dados, $status = 200) {
    http_response_code($status);
    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

/**
 * Traduz automaticamente o nome recebido da API de países.
 * Não precisa cadastrar os países manualmente.
 */
function traduzirPais($nome) {
    $arquivoCache = __DIR__ . '/traducoes_cache.json';
    $cache = [];

    if (file_exists($arquivoCache)) {
        $conteudo = @file_get_contents($arquivoCache);
        $dadosCache = json_decode($conteudo ?: '{}', true);

        if (is_array($dadosCache)) {
            $cache = $dadosCache;
        }
    }

    if (isset($cache[$nome])) {
        return $cache[$nome];
    }

    $url = 'https://api.mymemory.translated.net/get?' .
        http_build_query([
            'q' => $nome,
            'langpair' => 'en|pt'
        ]);

    $resposta = false;

    // Usa cURL quando disponível.
    if (function_exists('curl_init')) {
        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'AtlasMisterioso/1.0'
        ]);

        $resposta = curl_exec($curl);
        $statusHTTP = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($statusHTTP < 200 || $statusHTTP >= 300) {
            $resposta = false;
        }
    } else {
        $contexto = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => "User-Agent: AtlasMisterioso/1.0\r\n"
            ]
        ]);

        $resposta = @file_get_contents(
            $url,
            false,
            $contexto
        );
    }

    if ($resposta !== false) {
        $dados = json_decode($resposta, true);

        if (
            ($dados['responseStatus'] ?? 0) == 200 &&
            !empty($dados['responseData']['translatedText'])
        ) {
            $traducao = html_entity_decode(
                $dados['responseData']['translatedText'],
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

            if ($traducao !== '') {
                $cache[$nome] = $traducao;

                @file_put_contents(
                    $arquivoCache,
                    json_encode(
                        $cache,
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                    ),
                    LOCK_EX
                );

                return $traducao;
            }
        }
    }

    // Se o tradutor estiver indisponível, não interrompe o jogo.
    return $nome;
}

// Verifica a ação solicitada.
$acao = $_GET['action'] ?? 'random';

if ($acao !== 'random') {
    responder(['erro' => 'Ação inválida.'], 400);
}

// Guarda a lista de países em cache por 24 horas.
$arquivoPaises = __DIR__ . '/countries_cache.json';
$paises = null;

if (
    file_exists($arquivoPaises) &&
    time() - filemtime($arquivoPaises) < 86400
) {
    $paises = json_decode(
        file_get_contents($arquivoPaises),
        true
    );
}

// Busca os países somente quando necessário.
if (!is_array($paises) || empty($paises)) {
    $url = 'https://countries.dev/countries?full=true';

    $contexto = stream_context_create([
        'http' => [
            'timeout' => 15,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\n"
        ]
    ]);

    $resposta = @file_get_contents($url, false, $contexto);

    if ($resposta === false) {
        responder([
            'erro' => 'Não foi possível acessar a API de países.'
        ], 502);
    }

    $paises = json_decode($resposta, true);

    if (!is_array($paises) || empty($paises)) {
        responder([
            'erro' => 'A API retornou dados inválidos.'
        ], 502);
    }

    @file_put_contents(
        $arquivoPaises,
        json_encode($paises, JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

// Sorteia um país.
$pais = $paises[array_rand($paises)];

// Aqui acontece a tradução automática!
$nomeOriginal = $pais['name'] ?? 'Unknown';
$nomeTraduzido = traduzirPais($nomeOriginal);

// Normaliza a capital.
$capital = $pais['capital'] ?? [];

if (is_string($capital)) {
    $capital = $capital !== '' ? [$capital] : [];
}

if (!is_array($capital)) {
    $capital = [];
}

// Normaliza os idiomas.
$idiomas = [];

foreach (($pais['languages'] ?? []) as $idioma) {
    if (is_array($idioma) && isset($idioma['name'])) {
        $codigo = $idioma['iso639_1'] ?? $idioma['name'];
        $idiomas[$codigo] = $idioma['name'];
    } elseif (is_string($idioma)) {
        $idiomas[] = $idioma;
    }
}

// Normaliza as moedas.
$moedas = [];

foreach (($pais['currencies'] ?? []) as $moeda) {
    if (!is_array($moeda)) {
        continue;
    }

    $codigo = $moeda['code'] ?? $moeda['name'] ?? 'XXX';

    $moedas[$codigo] = [
        'name' => $moeda['name'] ?? 'Não informado',
        'symbol' => $moeda['symbol'] ?? ''
    ];
}

// Obtém as bandeiras.
$bandeiraPng = $pais['flags']['png'] ?? '';
$bandeiraSvg = $pais['flags']['svg'] ?? '';

// Monta a resposta compatível com o script.js existente.
$paisFormatado = [
    'name' => [
        'common' => $nomeTraduzido,
        'official' => $nomeTraduzido,
        'nativeName' => []
    ],
    'translations' => [
        'por' => [
            'common' => $nomeTraduzido
        ]
    ],
    'cca3' => $pais['alpha3Code'] ?? '',
    'cca2' => $pais['alpha2Code'] ?? '',
    'capital' => $capital,
    'region' => $pais['region'] ?? 'Não informado',
    'subregion' => $pais['subregion'] ?? 'Não informado',
    'continents' => [$pais['region'] ?? 'Mundo'],
    'population' => $pais['population'] ?? 0,
    'area' => $pais['area'] ?? 0,
    'flags' => [
        'png' => $bandeiraPng,
        'svg' => $bandeiraSvg,
        'alt' => 'Bandeira de ' . $nomeTraduzido
    ],
    'flag' => $pais['flag'] ?? '',
    'languages' => $idiomas,
    'currencies' => $moedas,
    'landlocked' => null,
    'borders' => $pais['borders'] ?? [],
    'timezones' => $pais['timezones'] ?? []
];

responder([
    'country' => $paisFormatado,
    'total' => count($paises)
]);
?>
