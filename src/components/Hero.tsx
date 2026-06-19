import { ArrowRight } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import heroBg from '../assets/hero-bg.jpg';

export default function Hero() {
  const navigate = useNavigate();

  const scrollToSection = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <section id="inicio" className="px-0 animate-fade-up">
      <div className="relative overflow-hidden bg-black">
        {/* Hero background image */}
        <img
          src={heroBg}
          alt=""
          aria-hidden="true"
          className="absolute inset-0 z-0 h-full w-full object-cover object-center"
        />
        {/* Dark overlay para legibilidade */}
        <div className="absolute inset-0 z-[1] bg-gradient-to-r from-black/65 via-black/45 to-black/20" />
        {/* Radial glow brand */}
        <div
          className="absolute inset-0 z-[2] opacity-70"
          style={{
            background:
              'radial-gradient(900px 420px at 18% 40%, rgba(16,82,224,0.40) 0%, rgba(16,82,224,0.10) 45%, rgba(0,0,0,0) 70%)',
          }}
        />
        {/* Subtle grid */}
        <div
          className="absolute inset-0 z-[2] opacity-[0.08]"
          style={{
            backgroundImage:
              'linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px)',
            backgroundSize: '56px 56px',
          }}
        />
        {/* Brand line */}
        <div className="absolute left-0 right-0 top-0 h-px bg-gradient-to-r from-transparent via-brand/70 to-transparent" />

        <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 pt-28 md:pt-36 pb-16 md:pb-24 min-h-[420px] md:min-h-[560px] flex items-center">
          <div className="max-w-3xl">
            <div className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white/80">
              <span className="inline-block h-2 w-2 rounded-full bg-brand shadow-[0_0_0_4px_rgba(16,82,224,0.18)]" />
              Sistemas sob medida • Sites • Automações
            </div>

            <h1 className="mt-6 text-4xl md:text-7xl font-extrabold text-white leading-[1.02]">
              Soluções{' '}
              <span className="bg-gradient-to-r from-white via-white to-brand bg-clip-text text-transparent">
                digitais
              </span>
              <br />
              com padrão de produto
            </h1>

            <p className="mt-6 text-lg md:text-2xl text-white/80 leading-relaxed">
              Desenvolvimento de sites e sistemas que passam confiança, convertem mais e reduzem trabalho com automação.
            </p>

            <div className="mt-10 flex flex-col sm:flex-row gap-3 sm:gap-4">
              <button onClick={() => scrollToSection('contato')} className="btn-brand w-full sm:w-auto">
                Solicitar proposta
                <ArrowRight size={20} />
              </button>
              <button onClick={() => navigate('/login')} className="btn-secondary w-full sm:w-auto">
                Acessar sistema
              </button>
            </div>

            <div className="mt-10 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-white/60">
              <span className="inline-flex items-center gap-2">
                <span className="h-1.5 w-1.5 rounded-full bg-white/40" />
                Rápido
              </span>
              <span className="inline-flex items-center gap-2">
                <span className="h-1.5 w-1.5 rounded-full bg-white/40" />
                Seguro
              </span>
              <span className="inline-flex items-center gap-2">
                <span className="h-1.5 w-1.5 rounded-full bg-white/40" />
                Escalável
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
