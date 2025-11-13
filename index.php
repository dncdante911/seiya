<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кондитерка & Фаершоу - Seiya</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .landing-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .landing-header {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            z-index: 10;
        }

        .landing-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .landing-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .sections-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .section-half {
            position: relative;
            flex: 1;
            min-height: 50vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }

        .section-half:hover {
            flex: 1.2;
        }

        /* Фоновые изображения - можно заменить на свои */
        .confectionery-section {
            background: linear-gradient(135deg, rgba(255, 107, 157, 0.95) 0%, rgba(255, 140, 66, 0.95) 100%),
                        url('uploads/confectionery-bg.jpg') center/cover;
        }

        .fireshow-section {
            background: linear-gradient(135deg, rgba(255, 69, 0, 0.95) 0%, rgba(255, 99, 71, 0.95) 100%),
                        url('uploads/fireshow-bg.jpg') center/cover;
        }

        /* Если изображения не загружены, будет красивый градиент */
        .confectionery-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            filter: brightness(1);
            transition: filter 0.5s ease;
        }

        .section-half:hover::before {
            filter: brightness(1.1);
        }

        .section-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            padding: 3rem;
            max-width: 600px;
            transform: scale(1);
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .section-half:hover .section-content {
            transform: scale(1.05);
        }

        .section-icon {
            font-size: 6rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
            transition: transform 0.3s ease;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .section-content h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.4);
            letter-spacing: 2px;
        }

        .section-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            line-height: 1.6;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .section-button {
            display: inline-block;
            padding: 1.2rem 3rem;
            background: white;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .confectionery-section .section-button {
            color: #ff6b9d;
        }

        .fireshow-section .section-button {
            color: #ff4500;
        }

        .section-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .section-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .section-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }

        .section-button span {
            position: relative;
            z-index: 1;
        }

        /* Декоративные элементы */
        .decorative-element {
            position: absolute;
            opacity: 0.1;
            pointer-events: none;
        }

        .confectionery-section .decorative-element {
            animation: rotate 20s linear infinite;
        }

        .fireshow-section .decorative-element {
            animation: rotate 15s linear infinite reverse;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Адаптивность */
        @media (min-width: 768px) {
            .sections-wrapper {
                flex-direction: row;
            }

            .section-half {
                min-height: 100vh;
            }
        }

        @media (max-width: 767px) {
            .landing-header h1 {
                font-size: 2rem;
            }

            .section-content h2 {
                font-size: 2rem;
            }

            .section-icon {
                font-size: 4rem;
            }

            .section-content p {
                font-size: 1.1rem;
            }
        }

        /* Плавное появление при загрузке */
        .fade-in {
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="landing-page">
        <header class="landing-header fade-in">
            <h1>🎂🔥 Seiya - Кондитерка & Фаершоу</h1>
            <p>Оберіть цікавий вам розділ</p>
        </header>

        <div class="sections-wrapper">
            <!-- Раздел Кондитерка -->
            <a href="confectionery/" class="section-half confectionery-section">
                <div class="section-content fade-in">
                    <!-- Декоративные элементы -->
                    <div class="decorative-element" style="top: 10%; left: 10%; font-size: 3rem;">🧁</div>
                    <div class="decorative-element" style="bottom: 10%; right: 10%; font-size: 2.5rem;">🍰</div>

                    <div class="section-icon">🎂</div>
                    <h2>Кондитерка</h2>
                    <p>
                        Авторські торти, тістечка, капкейки та багато іншого.<br>
                        Створіть торт своєї мрії з нашим конструктором!
                    </p>
                    <div class="section-button">
                        <span>Перейти до каталогу →</span>
                    </div>
                </div>
            </a>

            <!-- Раздел Фаершоу -->
            <a href="fireshow/" class="section-half fireshow-section">
                <div class="section-content fade-in">
                    <!-- Декоративные элементы -->
                    <div class="decorative-element" style="top: 15%; right: 15%; font-size: 3rem;">✨</div>
                    <div class="decorative-element" style="bottom: 15%; left: 15%; font-size: 2.5rem;">🎭</div>

                    <div class="section-icon">🔥</div>
                    <h2>Фаершоу</h2>
                    <p>
                        Професійні вогняні шоу для будь-яких заходів.<br>
                        Яскраві виступи, які запам'ятаються надовго!
                    </p>
                    <div class="section-button">
                        <span>Дивитись портфоліо →</span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script>
        // Добавляем эффект параллакса при движении мыши
        document.querySelectorAll('.section-half').forEach(section => {
            section.addEventListener('mousemove', (e) => {
                const rect = section.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const percentX = (x - centerX) / centerX;
                const percentY = (y - centerY) / centerY;

                const icon = section.querySelector('.section-icon');
                if (icon) {
                    icon.style.transform = `translate(${percentX * 20}px, ${percentY * 20}px)`;
                }
            });

            section.addEventListener('mouseleave', () => {
                const icon = section.querySelector('.section-icon');
                if (icon) {
                    icon.style.transform = 'translate(0, 0)';
                }
            });
        });
    </script>
</body>
</html>
