import { ArrowRight } from 'lucide-react';

export default function Hero() {
  const scrollToSection = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <section id="inicio" className="pt-32 pb-20 px-6">
      <div className="max-w-7xl mx-auto">
        <div className="max-w-4xl">
          <h1 className="text-5xl md:text-7xl font-bold text-black leading-tight mb-6">
            Soluções digitais, sistemas e ensino sob medida
          </h1>
          <p className="text-xl md:text-2xl text-gray-600 mb-10 leading-relaxed">
            Tecnologia aplicada para resolver problemas reais, ensinar com clareza e criar soluções escaláveis.
          </p>
          <div className="flex flex-col sm:flex-row gap-4">
            <button
              onClick={() => scrollToSection('contato')}
              className="px-8 py-4 bg-black text-white font-medium rounded hover:bg-gray-900 transition-colors flex items-center justify-center gap-2"
            >
              Solicitar proposta
              <ArrowRight size={20} />
            </button>
            <button className="px-8 py-4 border-2 border-[#1052E0] text-[#1052E0] font-medium rounded hover:bg-[#1052E0] hover:text-white transition-colors">
              Acessar sistema
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}
