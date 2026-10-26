
const $ = id => document.getElementById(id);

const game = {
    country: null,
    score: 0,
    solved: 0,
    streak: 0,
    round: 1,
    hints: [],
    finished: false,
    usedCountries: new Set()
};

const hints = [
    {
        key: 'continente',
        icon: '⌖',
        title: 'Continente',
        get: c => (c.continents || [c.region || 'Não informado']).join(', ')
    },
    {
        key: 'capital',
        icon: '⌂',
        title: 'Capital',
        get: c => (c.capital || ['Não informada']).join(', ')
    },
    {
        key: 'idioma',
        icon: '◉',
        title: 'Idioma(s)',
        get: c => Object.values(c.languages || {}).join(', ') || 'Não informado'
    },
    {
        key: 'moeda',
        icon: '¤',
        title: 'Moeda',
        get: c => Object.values(c.currencies || {})
            .map(x => `${x.name}${x.symbol ? ` (${x.symbol})` : ''}`)
            .join(', ') || 'Não informada'
    },
    {
        key: 'populacao',
        icon: '♙',
        title: 'População',
        get: c => c.population
            ? Number(c.population).toLocaleString('pt-BR') + ' habitantes'
            : 'Não informada'
    },
    {
        key: 'area',
        icon: '▦',
        title: 'Área territorial',
        get: c => c.area
            ? Number(c.area).toLocaleString('pt-BR') + ' km²'
            : 'Não informada'
    },
    {
        key: 'litoral',
        icon: '≈',
        title: 'Acesso ao mar',
        get: c => c.landlocked
            ? 'Não possui litoral'
            : 'Possui litoral'
    },
    {
        key: 'fuso',
        icon: '◷',
        title: 'Fuso horário',
        get: c => (c.timezones || ['Não informado']).join(', ')
    }
];

function normalize(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9 ]/g, '')
        .replace(/\s+/g, ' ');
}

function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
}

function getName(country) {
    return country.translations?.por?.common
        || country.name?.common
        || 'País desconhecido';
}

async function loadCountry() {
    game.finished = true;
    $('guess-button').disabled = true;
    $('hint-button').disabled = true;
    $('skip-button').disabled = true;

    $('feedback').textContent = 'Consultando o atlas...';
    $('feedback').className = '';

    $('reveal').classList.add('hidden');

    try {
        let country = null;

        // Evita repetir países já descobertos nesta sessão.
        for (let attempt = 0; attempt < 20; attempt++) {
            const response = await fetch('api.php?action=random', {
                cache: 'no-store'
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Erro ao consultar a API.');
            }

            if (!data.country) {
                throw new Error('Nenhum país foi recebido.');
            }

            const code = data.country.cca3;

            if (!game.usedCountries.has(code)) {
                country = data.country;
                break;
            }
        }

        // Se muitos países já foram usados, permite uma nova tentativa.
        if (!country) {
            game.usedCountries.clear();

            const response = await fetch('api.php?action=random');
            const data = await response.json();

            if (!response.ok || !data.country) {
                throw new Error(data.error || 'Não foi possível carregar um país.');
            }

            country = data.country;
        }

        game.country = country;
        game.usedCountries.add(country.cca3);
        game.hints = [];
        game.finished = false;

        $('round').textContent = String(game.round).padStart(2, '0');
        $('flag').src = country.flags?.png || country.flags?.svg || '';
        $('flag').alt = 'Bandeira misteriosa';
        $('guess').value = '';
        $('guess').disabled = false;
        $('guess-button').disabled = false;
        $('hint-button').disabled = false;
        $('skip-button').disabled = false;

        $('feedback').textContent = '';
        $('hint-button').textContent = '✧ Revelar uma pista (3 disponíveis)';

        renderClues();
        $('guess').focus();

    } catch (error) {
        $('feedback').textContent =
            error.message + ' Verifique a conexão e tente novamente.';
        $('feedback').className = 'error';
        $('guess-button').disabled = false;
    }
}

function renderClues() {
    const list = $('clues');
    list.innerHTML = '';

    hints.slice(0, 3).forEach(hint => {
        const revealed = game.hints.includes(hint.key);

        const item = document.createElement('div');
        item.className = 'clue';

        const value = revealed
            ? escapeHTML(hint.get(game.country))
            : 'Revele uma pista para descobrir';

        item.innerHTML = `
            <span class="clue-icon">${hint.icon}</span>
            <div class="clue-content">
                <b>${hint.title}</b>
                <small>${value}</small>
            </div>
            <span>${revealed ? '✓' : '▧'}</span>
        `;

        list.appendChild(item);
    });
}

function revealHint() {
    if (!game.country || game.finished) return;

    const hint = hints.find(h => !game.hints.includes(h.key));

    if (!hint) {
        $('feedback').textContent = 'Todas as pistas já foram reveladas!';
        return;
    }

    game.hints.push(hint.key);

    renderClues();

    const remaining = Math.max(0, 3 - game.hints.length);

    $('hint-button').textContent =
        remaining > 0
            ? `✧ Revelar uma pista (${remaining} disponíveis)`
            : 'Todas as pistas iniciais reveladas';

    $('feedback').textContent = `Nova pista: ${hint.title}.`;
    $('feedback').className = '';

    // Após as três pistas iniciais, ainda é possível jogar normalmente.
    $('hint-button').disabled = game.hints.length >= 3;
}

function checkGuess(event) {
    event.preventDefault();

    if (!game.country || game.finished) return;

    const answer = normalize($('guess').value);

    if (!answer) return;

    const country = game.country;

    const acceptedNames = [
        country.name?.common,
        country.name?.official,
        country.translations?.por?.common,
        country.translations?.por?.official,
        ...Object.values(country.name?.nativeName || {})
            .flatMap(n => [n.common, n.official])
    ].filter(Boolean).map(normalize);

    if (acceptedNames.includes(answer)) {
        const points = Math.max(100, 500 - game.hints.length * 100);

        game.score += points;
        game.solved++;
        game.streak++;
        game.finished = true;

        $('feedback').textContent = `Resposta correta! +${points} pontos!`;
        $('feedback').className = 'success';

        $('guess').disabled = true;
        $('guess-button').disabled = true;
        $('hint-button').disabled = true;
        $('skip-button').disabled = true;

        updateScore();
        showReveal();

    } else {
        game.streak = 0;

        $('feedback').textContent =
            'Ainda não! Tente novamente ou use uma pista.';
        $('feedback').className = 'error';

        updateScore();
        $('guess').select();
    }
}

function showReveal() {
    const c = game.country;

    $('reveal-flag').src = c.flags?.png || c.flags?.svg || '';
    $('reveal-name').textContent = getName(c);

    $('reveal-continent').textContent =
        (c.continents || [c.region || 'Mundo']).join(' · ').toUpperCase();

    $('reveal-capital').textContent =
        `Capital: ${(c.capital || ['Não informada']).join(', ')}`;

    $('reveal-fact').textContent = c.landlocked
        ? 'Curiosidade: este país não possui litoral.'
        : 'Curiosidade: este país possui acesso ao mar.';

    const facts = [
        [
            'Idioma(s)',
            Object.values(c.languages || {}).join(', ') || 'Não informado'
        ],
        [
            'Moeda',
            Object.values(c.currencies || {})
                .map(x => `${x.name}${x.symbol ? ` (${x.symbol})` : ''}`)
                .join(', ') || 'Não informada'
        ],
        [
            'População',
            c.population
                ? Number(c.population).toLocaleString('pt-BR') + ' habitantes'
                : 'Não informada'
        ],
        [
            'Área',
            c.area
                ? Number(c.area).toLocaleString('pt-BR') + ' km²'
                : 'Não informada'
        ],
        [
            'Fronteiras terrestres',
            c.borders?.length
                ? `Faz fronteira com ${c.borders.length} países.`
                : 'Não possui fronteiras terrestres listadas.'
        ],
        [
            'Fuso horário',
            (c.timezones || ['Não informado']).join(', ')
        ]
    ];

    $('facts').innerHTML = facts.map(([title, value]) => `
        <div class="fact">
            <span>${escapeHTML(title)}</span>
            <b>${escapeHTML(value)}</b>
        </div>
    `).join('');

    $('reveal').classList.remove('hidden');
    $('reveal').scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

function updateScore() {
    $('score').textContent = game.score.toLocaleString('pt-BR');
    $('solved').textContent = game.solved;
    $('streak').textContent = game.streak;

    $('progress-text').textContent =
        `${game.solved} ${game.solved === 1 ? 'país descoberto' : 'países descobertos'}`;

    $('progress-bar').style.width =
        `${Math.min(100, game.solved * 10)}%`;
}

function skipCountry() {
    if (!game.country || game.finished) return;

    game.streak = 0;
    game.finished = true;

    $('feedback').textContent =
        `O país era ${getName(game.country)}. Veja os dados e tente outro!`;
    $('feedback').className = '';

    $('guess').disabled = true;
    $('guess-button').disabled = true;
    $('hint-button').disabled = true;
    $('skip-button').disabled = true;

    updateScore();
    showReveal();
}

$('guess-form').addEventListener('submit', checkGuess);
$('hint-button').addEventListener('click', revealHint);
$('skip-button').addEventListener('click', skipCountry);

$('next-button').addEventListener('click', () => {
    game.round++;
    loadCountry();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

loadCountry();
