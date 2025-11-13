<?php
/**
 * Переключатель языка
 */
$current_lang = getCurrentLanguage();
$current_url = $_SERVER['REQUEST_URI'];
?>
<div class="language-switcher">
    <?php foreach (AVAILABLE_LANGUAGES as $lang): ?>
        <a href="/set_language.php?lang=<?= $lang ?>&redirect=<?= urlencode($current_url) ?>"
           class="lang-btn <?= $current_lang === $lang ? 'active' : '' ?>"
           title="<?= getLanguageName($lang) ?>">
            <span class="lang-flag"><?= getLanguageFlag($lang) ?></span>
            <span class="lang-code"><?= strtoupper($lang) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<style>
.navbar-lang {
    margin-left: auto;
    display: flex;
    align-items: center;
}

.language-switcher {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.lang-btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 25px;
    text-decoration: none;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}

.lang-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    transform: translateY(-2px);
}

.lang-btn.active {
    background: linear-gradient(135deg, var(--primary-color, #667eea), var(--secondary-color, #764ba2));
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.lang-flag {
    font-size: 1.2rem;
}

.lang-code {
    font-size: 0.9rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .navbar-lang {
        margin-left: 0;
        width: 100%;
        justify-content: center;
        padding: 1rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .language-switcher {
        margin-top: 0;
        justify-content: center;
        width: 100%;
    }

    .lang-btn {
        padding: 0.4rem 0.8rem;
    }

    .lang-code {
        display: none;
    }

    .lang-flag {
        font-size: 1.5rem;
    }
}
</style>
