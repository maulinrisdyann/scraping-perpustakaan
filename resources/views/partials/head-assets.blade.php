<script>
    // Set tema sebelum apa pun dirender, biar gak ada flash warna salah (FOUC).
    // Default gelap kalau belum pernah pilih (sesuai desain awal).
    if (localStorage.theme === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        document.documentElement.classList.add('dark');
    }
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    bg: 'rgb(var(--color-bg) / <alpha-value>)',
                    surface: 'rgb(var(--color-surface) / <alpha-value>)',
                    violet: 'rgb(var(--color-violet) / <alpha-value>)',
                    teal: 'rgb(var(--color-teal) / <alpha-value>)',
                    danger: 'rgb(var(--color-danger) / <alpha-value>)',
                    success: 'rgb(var(--color-success) / <alpha-value>)',
                    positive: 'rgb(var(--color-positive) / <alpha-value>)',
                    info: 'rgb(var(--color-info) / <alpha-value>)',
                    txprimary: 'rgb(var(--color-txprimary) / <alpha-value>)',
                    txsecondary: 'rgb(var(--color-txsecondary) / <alpha-value>)',
                },
                fontFamily: {
                    display: ['"Space Grotesk"', 'sans-serif'],
                    body: ['Inter', 'sans-serif'],
                },
                borderRadius: {
                    '2xl': '1rem',
                },
            },
        },
    };
</script>

<style>
    :root {
        /* Light mode -- tetap brand futuristik (bukan cream/terracotta default AI), cool-toned */
        --color-bg: 245 247 251;
        --color-surface: 255 255 255;
        --color-violet: 108 92 231;
        --color-teal: 0 168 150;
        --color-danger: 214 41 74;
        --color-success: 0 150 120;
        --color-positive: 0 166 81;
        --color-info: 37 99 235;
        --color-txprimary: 15 20 32;
        --color-txsecondary: 91 100 114;
        --glass-bg: rgba(255, 255, 255, 0.75);
        --glass-border: rgba(15, 20, 32, 0.08);
    }
    :root.dark {
        --color-bg: 11 14 20;
        --color-surface: 20 24 33;
        --color-violet: 108 92 231;
        --color-teal: 0 229 199;
        --color-danger: 255 77 109;
        --color-success: 0 229 160;
        --color-positive: 0 230 118;
        --color-info: 59 158 255;
        --color-txprimary: 245 247 250;
        --color-txsecondary: 139 149 165;
        --glass-bg: rgba(20, 24, 33, 0.7);
        --glass-border: rgba(255, 255, 255, 0.08);
    }

    body { font-family: 'Inter', sans-serif; }
    .font-display { font-family: 'Space Grotesk', sans-serif; }
    .glass {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
    }
    .glow-hover {
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .glow-hover:hover {
        transform: scale(1.02);
        border-color: rgba(108, 92, 231, 0.5);
        box-shadow: 0 0 24px rgba(108, 92, 231, 0.15);
    }
    .tabular-nums { font-variant-numeric: tabular-nums; }
    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-slide-in {
        animation: fadeSlideIn 0.5s ease-out both;
    }
    [x-cloak] { display: none !important; }
</style>
