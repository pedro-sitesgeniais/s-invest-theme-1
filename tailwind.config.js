module.exports = {
  content: [
    './*.php',
    './**/*.php',
    './assets/src/js/**/*.js',
    './components/**/*.php',
    './templates/**/*.php',
    './resources/**/*.js',
    './inc/**/*.php',
    // Adiciona novos paths para os arquivos otimizados
    './page-*.php',
    './single-*.php',
    './archive-*.php'
  ],
  
  safelist: [
    // Suas classes existentes
    'bg-red-500',
    'bg-green-500',
    'x-cloak',
    'x-data',
    'x-show',
    
    // Classes para Alpine.js - OTIMIZADO
    'animate-spin',
    'animate-pulse',
    'animate-bounce',
    'opacity-0',
    'opacity-100',
    'translate-x-0',
    'translate-x-4',
    '-translate-x-4',
    'translate-y-0',
    'translate-y-2',
    '-translate-y-2',
    'scale-95',
    'scale-100',
    
    // Estados de mensagens - OTIMIZADO
    'bg-red-50',
    'bg-green-50',
    'bg-blue-50',
    'bg-yellow-50',
    'text-red-600',
    'text-green-600',
    'text-blue-600',
    'text-yellow-600',
    'text-red-800',
    'text-green-800',
    'text-blue-800',
    'text-yellow-800',
    'border-red-200',
    'border-green-200',
    'border-blue-200',
    'border-yellow-200',
    
    // Nova cor secundária - ADICIONADO
    'bg-secondary-new',
    'text-secondary-new',
    'border-secondary-new',
    'hover:bg-secondary-new',
    'hover:text-secondary-new',
    'bg-secondary-new/10',
    'bg-secondary-new/20',
    'bg-secondary-new/30',
    'bg-secondary-new/50',
    'bg-secondary-new/75',
    'bg-secondary-new/90',
    
    // Classes para loading states
    'disabled:opacity-50',
    'disabled:cursor-not-allowed',
    
    // Suas classes de aspecto
    { pattern: /aspect-.*/ },

    // Suas classes dinâmicas do Alpine - EXPANDIDO
    {
      pattern: /(bg|text|border)-(blue|gray|red|green|yellow|purple|indigo|cyan)-(50|100|200|300|400|500|600|700|800|900)/,
      variants: ['hover', 'focus', 'disabled', 'active']
    },
    
    // Classes para transições Alpine.js
    {
      pattern: /(transition|duration|ease)-.*/,
    },
    
    // Classes para transform
    {
      pattern: /(translate|scale|rotate)-.*/,
    }
  ],
  
  theme: {
    aspectRatio: {
      1: '1',
      2: '2',
      3: '3',
      4: '4',
    },
    extend: {
      // CORES ATUALIZADAS para o tema S-Invest
      colors: {
        // Paleta principal
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#000E35', // Cor principal primária
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
          DEFAULT: '#000E35',
        },
        
        // Paleta secundária - NOVA COR #2ED2F8
        secondary: {
          50: '#f0fdff',
          100: '#ccfafe',
          200: '#a6f4fd',
          300: '#67ecfc',
          400: '#2ed2f8', // NOVA COR PRINCIPAL
          500: '#06bfdb',
          600: '#0891b2',
          700: '#0e7490',
          800: '#155e75',
          900: '#164e63',
          DEFAULT: '#2ED2F8',
        },
        
        // Cor de acento
        accent: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#2072D6', // Cor de acento
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
          DEFAULT: '#2072D6',
        },
        
        // Estados
        success: '#22c55e',
        warning: '#f59e0b',
        error: '#ef4444',
        info: '#3b82f6',
        
        // Cores específicas do tema
        'secondary-new': '#2ED2F8', // Alias para facilitar uso
      },
      
      // ANIMAÇÕES OTIMIZADAS
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'fade-out': 'fadeOut 0.5s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'slide-down': 'slideDown 0.3s ease-out',
        'bounce-gentle': 'bounceGentle 1s ease-in-out infinite',
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'blob': 'blob 7s infinite',
        'glow': 'glow 2s ease-in-out infinite alternate', // NOVA ANIMAÇÃO
      },
      
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        fadeOut: {
          '0%': { opacity: '1' },
          '100%': { opacity: '0' }
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' }
        },
        slideDown: {
          '0%': { transform: 'translateY(-10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' }
        },
        bounceGentle: {
          '0%, 100%': { transform: 'translateY(-5%)' },
          '50%': { transform: 'translateY(0)' }
        },
        blob: {
          '0%': { transform: 'translate(0px, 0px) scale(1)' },
          '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
          '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
          '100%': { transform: 'translate(0px, 0px) scale(1)' }
        },
        // NOVA ANIMAÇÃO PARA A COR SECUNDÁRIA
        glow: {
          '0%': { boxShadow: '0 0 20px rgba(46, 210, 248, 0.3)' },
          '100%': { boxShadow: '0 0 40px rgba(46, 210, 248, 0.6)' }
        }
      },
      
      // SOMBRAS MELHORADAS COM NOVA COR
      boxShadow: {
        'glow': '0 0 20px rgba(46, 210, 248, 0.15)', // ATUALIZADO
        'glow-lg': '0 0 40px rgba(46, 210, 248, 0.2)', // ATUALIZADO
        'glow-secondary': '0 0 20px rgba(46, 210, 248, 0.25)', // NOVO
        'glow-secondary-lg': '0 0 40px rgba(46, 210, 248, 0.35)', // NOVO
        'soft': '0 2px 15px rgba(0, 0, 0, 0.08)',
        'soft-lg': '0 8px 30px rgba(0, 0, 0, 0.12)'
      },
      
      // BACKDROP FILTER
      backdropBlur: {
        'xs': '2px',
      },
      
      // GRADIENTES COM NOVA COR
      backgroundImage: {
        'gradient-secondary': 'linear-gradient(135deg, #2ED2F8, #06bfdb)',
        'gradient-primary-secondary': 'linear-gradient(135deg, #000E35, #2ED2F8)',
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
      },
      
      // DELAYS PARA ANIMAÇÕES
      transitionDelay: {
        '100': '100ms',
        '200': '200ms',
        '300': '300ms',
        '500': '500ms',
        '1000': '1000ms',
        '2000': '2000ms',
        '4000': '4000ms'
      },
      
      // SPACING PERSONALIZADO
      spacing: {
        '18': '4.5rem',
        '72': '18rem',
        '88': '22rem',
        '128': '32rem',
      },
      
      // BORDER RADIUS PERSONALIZADO
      borderRadius: {
        '4xl': '2rem',
        '5xl': '2.5rem',
      },
      
      // FONT SIZES PERSONALIZADOS
      fontSize: {
        '2xs': ['0.625rem', { lineHeight: '0.75rem' }],
        '6xl': ['3.75rem', { lineHeight: '1' }],
        '7xl': ['4.5rem', { lineHeight: '1' }],
        '8xl': ['6rem', { lineHeight: '1' }],
        '9xl': ['8rem', { lineHeight: '1' }],
      }
    }
  },
  
  variants: {
    aspectRatio: ['responsive', 'hover'],
    // VARIANTES ESTENDIDAS
    extend: {
      opacity: ['disabled'],
      cursor: ['disabled'],
      backgroundColor: ['active', 'disabled', 'group-hover'],
      transform: ['hover', 'focus', 'active', 'group-hover'],
      scale: ['hover', 'focus', 'active', 'group-hover'],
      translate: ['hover', 'focus', 'active', 'group-hover'],
      boxShadow: ['hover', 'focus', 'active', 'group-hover'],
    }
  },
  
  plugins: [
    require('@tailwindcss/aspect-ratio')({
      className: 'aspect',
      prefix: ''
    }),
    
    // PLUGIN CUSTOMIZADO para utilitários específicos
    function({ addUtilities, addComponents, theme }) {
      // Utilitários para glassmorphism
      addUtilities({
        '.glass': {
          background: 'rgba(255, 255, 255, 0.8)',
          backdropFilter: 'blur(10px)',
          border: '1px solid rgba(255, 255, 255, 0.2)'
        },
        '.glass-dark': {
          background: 'rgba(0, 0, 0, 0.8)',
          backdropFilter: 'blur(10px)',
          border: '1px solid rgba(255, 255, 255, 0.1)'
        },
        // NOVO: Glass com cor secundária
        '.glass-secondary': {
          background: 'rgba(46, 210, 248, 0.1)',
          backdropFilter: 'blur(10px)',
          border: '1px solid rgba(46, 210, 248, 0.2)'
        }
      });
      
      // Componentes para botões com nova cor
      addComponents({
        '.btn': {
          padding: theme('spacing.2') + ' ' + theme('spacing.4'),
          borderRadius: theme('borderRadius.lg'),
          fontWeight: theme('fontWeight.semibold'),
          textAlign: 'center',
          transition: 'all 0.2s',
          cursor: 'pointer',
          display: 'inline-flex',
          alignItems: 'center',
          justifyContent: 'center',
          '&:disabled': {
            opacity: '0.5',
            cursor: 'not-allowed'
          }
        },
        '.btn-primary': {
          backgroundColor: theme('colors.primary.DEFAULT'),
          color: theme('colors.white'),
          '&:hover:not(:disabled)': {
            backgroundColor: theme('colors.primary.600'),
            transform: 'translateY(-1px)'
          }
        },
        // NOVO: Botão com cor secundária
        '.btn-secondary': {
          backgroundColor: theme('colors.secondary.DEFAULT'),
          color: theme('colors.white'),
          '&:hover:not(:disabled)': {
            backgroundColor: theme('colors.secondary.500'),
            transform: 'translateY(-1px)',
            boxShadow: theme('boxShadow.glow-secondary')
          }
        }
      });
      
      // Delays para animações
      addUtilities({
        '.animation-delay-100': { 'animation-delay': '100ms' },
        '.animation-delay-200': { 'animation-delay': '200ms' },
        '.animation-delay-300': { 'animation-delay': '300ms' },
        '.animation-delay-500': { 'animation-delay': '500ms' },
        '.animation-delay-1000': { 'animation-delay': '1000ms' },
        '.animation-delay-2000': { 'animation-delay': '2000ms' },
        '.animation-delay-4000': { 'animation-delay': '4000ms' }
      });
      
      // NOVOS UTILITÁRIOS para a cor secundária
      addUtilities({
        '.text-secondary-glow': {
          color: theme('colors.secondary.DEFAULT'),
          textShadow: '0 0 10px rgba(46, 210, 248, 0.5)'
        },
        '.border-glow-secondary': {
          borderColor: theme('colors.secondary.DEFAULT'),
          boxShadow: '0 0 10px rgba(46, 210, 248, 0.3)'
        },
        '.bg-gradient-secondary': {
          backgroundImage: 'linear-gradient(135deg, ' + theme('colors.secondary.400') + ', ' + theme('colors.secondary.600') + ')'
        }
      });
    }
  ],
};