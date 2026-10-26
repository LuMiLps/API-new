
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Atlas Misterioso | Jogo de países</title>

    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="#">
            <span class="logo">✦</span>
            <span>atlas.<small>MISTÉRIO MUNDIAL</small></span>
        </a>
        <span class="status">● EXPLORAÇÃO ATIVA</span>
    </header>

    <main class="container">
        <section class="intro">
            <span class="eyebrow">✧ DESAFIO GEOGRÁFICO</span>
            <h1>O mundo é seu.<br>
                <span>Você sabe onde está?</span>
            </h1>
            <p>Uma bandeira. Um país secreto. Descubra as pistas
                e prove que conhece o planeta.</p>
        </section>

        <section class="game">
            <article class="panel">
                <div class="panel-header">
                    <span>MISSÃO <b id="round">01</b></span>
                    <span class="level">NÍVEL EXPLORADOR</span>
                </div>

                <div class="flag-stage">
                    <div class="flag-frame">
                        <img id="flag" src="" alt="Bandeira misteriosa">
                    </div>
                    <span class="mystery">? PAÍS DESCONHECIDO</span>
                </div>

                <div class="question">
                    <span class="eyebrow">SUA MISSÃO</span>
                    <h2>Que país é este?</h2>
                    <p>Observe a bandeira e investigue as pistas.</p>

                    <form id="guess-form">
                        <input
                            id="guess"
                            type="text"
                            placeholder="Digite o nome do país..."
                            autocomplete="off"
                            required
                        >
                        <button type="submit" id="guess-button">
                            Confirmar ↗
                        </button>
                    </form>

                    <p id="feedback" aria-live="polite"></p>

                    <div class="actions">
                        <button type="button" id="hint-button">
                            ✧ Revelar uma pista
                        </button>
                        <button type="button" id="skip-button">
                            Pular país →
                        </button>
                    </div>
                </div>
            </article>

            <aside class="sidebar">
                <article class="panel side-panel">
                    <span class="eyebrow">ARQUIVO SECRETO</span>
                    <h3>Arquivo de pistas</h3>
                    <p class="subtext">Informações desbloqueadas</p>
                    <div id="clues" class="clues"></div>
                    <div class="tip">
                        ✦ Você ganha mais pontos se acertar
                        usando menos pistas!
                    </div>
                </article>

                <article class="panel side-panel">
                    <span class="eyebrow">SEU PROGRESSO</span>
                    <h3>Diário de exploração</h3>

                    <div class="stats">
                        <div>
                            <strong id="score">0</strong>
                            <small>PONTOS</small>
                        </div>
                        <div>
                            <strong id="solved">0</strong>
                            <small>DESCOBERTOS</small>
                        </div>
                        <div>
                            <strong id="streak">0</strong>
                            <small>SEGUIDOS</small>
                        </div>
                    </div>

                    <div class="progress-label">
                        <span>EXPEDIÇÃO</span>
                        <span id="progress-text">0 países</span>
                    </div>
                    <div class="progress">
                        <div id="progress-bar"></div>
                    </div>
                </article>
            </aside>
        </section>

        <section id="reveal" class="panel reveal hidden">
            <span class="eyebrow">DOSSIÊ DESBLOQUEADO</span>

            <div class="reveal-main">
                <img id="reveal-flag" src="" alt="Bandeira do país">
                <div>
                    <p id="reveal-continent" class="eyebrow"></p>
                    <h2 id="reveal-name"></h2>
                    <p id="reveal-capital"></p>
                    <p id="reveal-fact"></p>
                </div>
            </div>

            <div id="facts" class="facts"></div>

            <button id="next-button" class="next">
                Explorar outro país →
            </button>
        </section>

        <footer>
            ATLAS MISTERIOSO · EXPLORE O MUNDO, UMA BANDEIRA POR VEZ
        </footer>
    </main>
</body>
</html>
