<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chipta Qidirish</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div id="main-container" class="container mx-auto px-4 py-6 max-w-2xl">
        <div
            x-data="{
                status: 'loading', // loading, authenticated, error
                errorMessage: '',
                init() {
                    const webApp = window.Telegram?.WebApp;
                    if (!webApp || !webApp.initData) {
                        this.status = 'error';
                        this.errorMessage = 'Ilovani Telegram orqali oching.';
                        return;
                    }

                    // Set theme colors immediately
                    this.setTheme(webApp);

                    // Perform API authentication
                    fetch('{{ route('webapp.login') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                        },
                        body: JSON.stringify({ initData: webApp.initData })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Autentifikatsiya xatosi.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'ok') {
                            this.status = 'authenticated';
                            // Notify Livewire components that the user is now authenticated
                            Livewire.dispatch('user-authenticated');
                        } else {
                            throw new Error('Autentifikatsiya javobi noto\'g\'ri.');
                        }
                    })
                    .catch(error => {
                        this.status = 'error';
                        this.errorMessage = error.message || 'Noma\'lum xatolik yuz berdi.';
                    });
                },
                setTheme(webApp) {
                    webApp.ready();
                    webApp.expand();
                    const theme = webApp.themeParams;
                    const root = document.documentElement;
                    root.style.setProperty('--tg-theme-bg-color', theme.bg_color || '#ffffff');
                    root.style.setProperty('--tg-theme-text-color', theme.text_color || '#212529');
                    root.style.setProperty('--tg-theme-hint-color', theme.hint_color || '#999999');
                    root.style.setProperty('--tg-theme-link-color', theme.link_color || '#2481cc');
                    root.style.setProperty('--tg-theme-button-color', theme.button_color || '#2481cc');
                    root.style.setProperty('--tg-theme-button-text-color', theme.button_text_color || '#ffffff');
                    root.style.setProperty('--tg-theme-secondary-bg-color', theme.secondary_bg_color || '#f4f4f5');
                    document.body.style.backgroundColor = 'var(--tg-theme-bg-color)';
                    webApp.MainButton.setText('Yopish').show().onClick(() => webApp.close());
                }
            }"
        >
            <h1 class="text-2xl font-bold mb-6 text-center" style="color: var(--tg-theme-text-color, #212529);">Chipta Qidirish</h1>

            <template x-if="status === 'loading'">
                <p style="color: var(--tg-theme-text-color, #212529);">Autentifikatsiya qilinmoqda...</p>
            </template>

            <template x-if="status === 'error'">
                <p x-text="errorMessage" style="color: #ff4d4d;"></p>
            </template>

            <div x-show="status === 'authenticated'" style="display: none;">
                @livewire('ticket-search-manager')
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
