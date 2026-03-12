<?php
/**
 * Sistema de Tradução - Kelps Blog
 */

class LanguageManager {
    private static $currentLanguage = 'en';
    private static $translations = [
        'pt' => [
            'home' => 'Home',
            'create_post' => 'Criar Post',
            'notifications' => 'Notificações',
            'admin' => 'Admin',
            'profile' => 'Perfil',
            'logout' => 'Logout',
            'login' => 'Login',
            'register' => 'Registrar',
            'language' => 'Idioma',
            'portuguese' => 'Português (BR)',
            'english' => 'English',
            'open_menu' => 'Abrir menu',
            'close_menu' => 'Fechar menu',
            'recent_posts' => 'Posts Recentes da Comunidade',
            'no_posts' => 'Nenhum post encontrado',
            'read_more' => 'Ler mais',
            'followers' => 'Seguidores',
            'following' => 'Seguindo',
            'upvotes' => 'Upvotes',
            'comments' => 'Comentários',
            'sign_out' => 'Sair',
            'menu' => 'Menu',
            // index.php landing page
            'page_title' => 'Kelps Blog - Sua Rede Social',
            'hero_badge' => '🚀 Comunidade Ativa',
            'hero_title_share' => 'Compartilhe',
            'hero_title_knowledge' => 'Conhecimento',
            'hero_title_connect' => 'Conecte-se com',
            'hero_title_people' => 'Pessoas',
            'hero_description' => 'Junte-se à comunidade Kelps Blog e descubra um espaço onde ideias ganham vida. Publique artigos, participe de discussões e conecte-se com pessoas incríveis.',
            'create_free_account' => 'Criar Conta Grátis',
            'already_have_account' => 'Já tenho conta',
            'members' => 'Membros',
            'publications' => 'Publicações',
            'create_posts_card' => 'Crie Posts',
            'comment_card' => 'Comente',
            'interact_card' => 'Interaja',
            'connect_card' => 'Conecte-se',
            'why_choose' => 'Por que escolher o',
            'secure_reliable' => 'Seguro & Confiável',
            'secure_desc' => 'Seus dados estão protegidos com as melhores práticas de segurança do mercado.',
            'fast_modern' => 'Rápido & Moderno',
            'fast_desc' => 'Interface moderna e responsiva que funciona perfeitamente em qualquer dispositivo.',
            'markdown_support' => 'Suporte a Markdown',
            'markdown_desc' => 'Formate seus posts com Markdown para criar conteúdo rico e bem estruturado.',
            'notifications_title' => 'Notificações',
            'notifications_desc' => 'Fique por dentro de tudo com notificações em tempo real sobre suas interações.',
            'ready_to_start' => 'Pronto para começar?',
            'ready_desc' => 'Crie sua conta em segundos e comece a compartilhar suas ideias com o mundo.',
            'create_my_account' => 'Criar Minha Conta',
            'no_posts_first' => 'Nenhum post encontrado. Seja o primeiro a criar um post!',
            'login_to_upvote' => 'Faça login para dar upvote',
            'error_loading_posts' => 'Erro ao carregar posts. Tente novamente mais tarde.',
            'error_upvote' => 'Erro ao processar upvote',
            'locale' => 'pt-BR',
        ],
        'en' => [
            'home' => 'Home',
            'create_post' => 'Create Post',
            'notifications' => 'Notifications',
            'admin' => 'Admin',
            'profile' => 'Profile',
            'logout' => 'Logout',
            'login' => 'Login',
            'register' => 'Register',
            'language' => 'Language',
            'portuguese' => 'Português (BR)',
            'english' => 'English',
            'open_menu' => 'Open menu',
            'close_menu' => 'Close menu',
            'recent_posts' => 'Community Recent Posts',
            'no_posts' => 'No posts found',
            'read_more' => 'Read more',
            'followers' => 'Followers',
            'following' => 'Following',
            'upvotes' => 'Upvotes',
            'comments' => 'Comments',
            'sign_out' => 'Sign out',
            'menu' => 'Menu',
            // index.php landing page
            'page_title' => 'Kelps Blog - Your Social Network',
            'hero_badge' => '🚀 Active Community',
            'hero_title_share' => 'Share',
            'hero_title_knowledge' => 'Knowledge',
            'hero_title_connect' => 'Connect with',
            'hero_title_people' => 'People',
            'hero_description' => 'Join the Kelps Blog community and discover a space where ideas come to life. Publish articles, participate in discussions, and connect with amazing people.',
            'create_free_account' => 'Create Free Account',
            'already_have_account' => 'Already have an account',
            'members' => 'Members',
            'publications' => 'Posts',
            'create_posts_card' => 'Create Posts',
            'comment_card' => 'Comment',
            'interact_card' => 'Interact',
            'connect_card' => 'Connect',
            'why_choose' => 'Why choose',
            'secure_reliable' => 'Secure & Reliable',
            'secure_desc' => 'Your data is protected with the best security practices available.',
            'fast_modern' => 'Fast & Modern',
            'fast_desc' => 'Modern and responsive interface that works perfectly on any device.',
            'markdown_support' => 'Markdown Support',
            'markdown_desc' => 'Format your posts with Markdown to create rich and well-structured content.',
            'notifications_title' => 'Notifications',
            'notifications_desc' => 'Stay up to date with real-time notifications about your interactions.',
            'ready_to_start' => 'Ready to get started?',
            'ready_desc' => 'Create your account in seconds and start sharing your ideas with the world.',
            'create_my_account' => 'Create My Account',
            'no_posts_first' => 'No posts found. Be the first to create a post!',
            'login_to_upvote' => 'Log in to upvote',
            'error_loading_posts' => 'Error loading posts. Please try again later.',
            'error_upvote' => 'Error processing upvote',
            'locale' => 'en-US',
        ]
    ];

    public static function init() {
        // Verificar cookie de idioma
        if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], ['en', 'pt'])) {
            self::$currentLanguage = $_COOKIE['language'];
        } else {
            // Padrão: Inglês
            self::$currentLanguage = 'en';
        }
    }

    public static function getCurrentLanguage() {
        return self::$currentLanguage;
    }

    public static function setLanguage($lang) {
        if (in_array($lang, ['en', 'pt'])) {
            self::$currentLanguage = $lang;
            setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
            $_COOKIE['language'] = $lang;
        }
    }

    public static function t($key) {
        return self::$translations[self::$currentLanguage][$key] ?? self::$translations['en'][$key] ?? $key;
    }

    public static function isPortuguese() {
        return self::$currentLanguage === 'pt';
    }

    public static function isEnglish() {
        return self::$currentLanguage === 'en';
    }
}

// Inicializar gestor de idiomas
LanguageManager::init();

// Função helper para tradução
function __($key) {
    return LanguageManager::t($key);
}
