<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instruções - Assistente Virtual</title>
    <style>
        :root {
            --primary-blue: #0052A5;
            --accent-blue: #E1EEFA;
            --text-dark: #0B3B60;
            --bg-light: #F4F8FB;
            --card-bg: #FFFFFF;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        .header {
            background-color: var(--primary-blue);
            color: white;
            padding: 24px;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 8px 0;
            font-size: 22px;
        }

        .container {
            max-width: 800px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--accent-blue);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .card h2 {
            margin-top: 0;
            font-size: 18px;
            color: var(--primary-blue);
            border-bottom: 1px solid var(--accent-blue);
            padding-bottom: 8px;
        }

        .badge {
            display: inline-block;
            background-color: var(--accent-blue);
            color: var(--primary-blue);
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
        }

        ul {
            padding-left: 20px;
            margin: 8px 0;
        }

        li {
            margin-bottom: 8px;
        }

        .note {
            background-color: #FFF9E6;
            border-left: 4px solid #FFC107;
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 14px;
            color: #555;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--accent-blue);
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            color: var(--text-dark);
            background-color: var(--bg-light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            background-color: #fff;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(11, 59, 96, 0.5); /* Semi-transparent dark blue overlay */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.2s ease-out;
        }

        /* Modal Box */
        .modal-content {
            background: #FFFFFF;
            padding: 24px 30px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--accent-blue);
            animation: slideUp 0.25s ease-out;
        }

        /* Icon Styles */
        .modal-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin: 0 auto 16px auto;
        }

        .success-icon {
            background-color: #E6F4EA;
            color: #137333;
        }

        .error-icon {
            background-color: #FCE8E6;
            color: #C5221F;
        }

        /* Typography & Lists */
        .modal-title {
            margin: 0 0 10px 0;
            color: var(--text-dark);
            font-size: 20px;
        }

        .modal-text {
            color: #555555;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .modal-error-list {
            text-align: left;
            color: #C5221F;
            font-size: 13px;
            padding-left: 20px;
            margin-bottom: 20px;
        }

        /* Modal Buttons */
        .btn-modal {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-success-modal {
            background-color: var(--primary-blue);
            color: white;
        }

        .btn-error-modal {
            background-color: #D93025;
            color: white;
        }

        .btn-modal:hover {
            opacity: 0.9;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    @if(session('success'))
    <div id="modal-success" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon success-icon">✓</div>
            <h3 class="modal-title">Mensagem Enviada!</h3>
            <p class="modal-text">{{ session('success') }}</p>
            <button class="btn-modal btn-success-modal" onclick="closeModal('modal-success')">OK</button>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div id="modal-error" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon error-icon">✕</div>
            <h3 class="modal-title">Ops! Algo deu errado</h3>
            <ul class="modal-error-list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button class="btn-modal btn-error-modal" onclick="closeModal('modal-error')">Tentar Novamente</button>
        </div>
    </div>
    @endif

    <header class="header">
        <h1>Guia de Uso - Assistente Virtual</h1>
        <p>Saiba como tirar suas dúvidas utilizando nosso assistente</p>
    </header>

    <main class="container">
        <section class="card">
            <h2>Como Funciona o Assistente</h2>
            <ul>
                <li><strong>Apenas Tira Dúvidas:</strong> O assistente foi desenvolvido exclusivamente para responder perguntas sobre o sistema. Ele não executa ações ou comandos dentro do Transnet.</li>
                <li><strong>Perguntas Diretas:</strong> Vá direto ao ponto! Você não precisa incluir saudações como <em>"Olá"</em>, <em>"Tudo bem?"</em> ou <em>"Pode me ajudar?"</em>. Apenas digite sua dúvida principal (ex: <em>"Como cadastrar um veículo?"</em>).</li>
                <li><strong>Sem Comandos do Sistema:</strong> O chat não entende comandos textuais de navegação ou encerramento como <em>"Sair"</em>, <em>"Fechar"</em> ou <em>"Voltar"</em>.</li>
                <li><strong>Avaliação da Resposta:</strong> Sempre que possível, avalie a resposta gerada pelo assistente utilizando os botões 👍 ou 👎, isso ajuda a equipe na melhoria do assistente.</li>
            </ul>
        </section>

        <section class="card">
            <h2>Filtrando por Módulo</h2>
            <p>Para obter respostas mais precisas, você pode navegar pelos tópicos do sistema:</p>
            <ul>
                <li>Você pode filtrar por <strong>Módulo e Submódulo</strong> (Exemplo: <span class="badge">Frota</span> ➔ <span class="badge">Manutenção</span>) ou simplesmente fazer uma pergunta direta sem aplicar nenhum filtro.</li>
                <li>Caso queira trocar a categoria atual, basta clicar no botão <span class="badge">Alterar categoria</span> localizado no topo da janela do assistente.</li>
            </ul>
        </section>

        <section class="card">
            <h2>Controles e Configurações de Tela</h2>
            <ul>
                <li><strong>Fechar a janela do chat:</strong> Para fechar a conversa, clique no botão <strong style="color: var(--primary-blue);">X</strong> no canto superior direito da janela do assistente.</li>
                <li><strong>Ocultar o botão do assistente:</strong> Caso não queira ver o ícone do assistente na tela do Transnet, desmarque a opção <strong>"Exibir assistente"</strong> no rodapé do site (localizada ao lado da opção <em>"Exibir microfone"</em>).</li>
            </ul>
        </section>

        <section class="card">
            <h2>Perguntas Frequentes (FAQ)</h2>
            <details style="margin-bottom: 10px; cursor: pointer;">
                <summary><strong>O assistente não respondeu minha dúvida, o que fazer?</strong></summary>
                <p style="padding-left: 15px;">Tente reformular sua mensagem utilizando palavras-chave mais simples ou utilize o botão <strong>"Filtrar por módulo"</strong> para navegar manualmente.</p>
            </details>
            <details style="margin-bottom: 10px; cursor: pointer;">
                <summary><strong>Mesmo filtrando por módulo, o assistente não respondeu minha dúvida, e agora?</strong></summary>
                <p style="padding-left: 15px;">Faça uma pergunta direta sem utilização do filtro.</p>
            </details>
            <details style="margin-bottom: 10px; cursor: pointer;">
                <summary><strong>Apenas uma janela branca aparece quando abro o assistente virtual, como proseguir?</strong></summary>
                <p style="padding-left: 15px;">Utilize o formulário abaixo para enviar uma mensagem ao nosso suporte.</p>
            </details>
        </section>

        <section class="card">
            <h2>Envie sua Dúvida ou Problema</h2>
            <p>Ainda precisa de ajuda? Preencha o formulário abaixo para entrar em contato com nossa equipe de suporte do Assistente Virtual.</p>
            
            <form action="/help/contact" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">Nome completo *</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Digite seu nome" required>
                </div>

                <div class="form-group">
                    <label for="company_name">Nome da empresa *</label>
                    <input type="text" id="company_name" name="company_name" class="form-control" placeholder="Digite o nome da empresa" required>
                </div>

                <div class="form-group">
                    <label for="description">Descrição do problema ou dúvida *</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Descreva em detalhes o seu problema..." maxlength="255" required></textarea>
                </div>

                <button type="submit" class="btn-submit">Enviar Mensagem</button>
            </form>
        </section>
    </main>
    <script>
    function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>