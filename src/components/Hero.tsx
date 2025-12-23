import { ArrowRight } from 'lucide-react';
import heroImage from '../../hero.png';

export default function Hero() {
  const scrollToSection = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <section id="inicio" className="px-0 bg-white animate-fade-up">
      <div
        className="relative overflow-hidden rounded-b-3xl"
        style={{
          backgroundImage: `url(${heroImage})`,
          backgroundSize: 'cover',
          backgroundPosition: 'center',
          backgroundRepeat: 'no-repeat',
        }}
      >
        <div className="relative max-w-7xl mx-auto px-6 pt-28 md:pt-32 pb-20 min-h-[420px] md:min-h-[520px] flex items-center">
          <div className="max-w-4xl">
            <h1 className="text-4xl md:text-7xl font-bold text-white leading-tight mb-6">
              Soluções digitais
              <br />
              sistemas e ensino
              <br />
              sob medida
            </h1>
            <p className="text-lg md:text-2xl text-gray-200 mb-10 leading-relaxed">
              Tecnologia aplicada para resolver problemas reais,
              <br />
              criando soluções digitais para o seu negócio.
            </p>
            <div className="flex flex-col sm:flex-row gap-4">
              <button
                onClick={() => scrollToSection('contato')}
                className="inline-flex items-center justify-center gap-2 rounded-md bg-white/95 px-8 py-3 text-sm font-semibold text-black shadow-lg shadow-black/40 transition-transform transition-colors duration-200 hover:-translate-y-0.5 hover:bg-white"
              >
                Solicitar proposta
                <ArrowRight size={20} />
              </button>
              <button className="inline-flex items-center justify-center gap-2 rounded-md border border-[#1052E0] bg-black/40 px-8 py-3 text-sm font-medium text-[#86a7ff] backdrop-blur-sm transition-transform transition-colors duration-200 hover:-translate-y-0.5 hover:bg-[#1052E0] hover:text-white">
                Acessar sistema
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
