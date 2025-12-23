import { Menu, X } from 'lucide-react';
import logoMvlBranco from '../../logo/logo_mvl-2_branco.png';
import { useEffect, useState } from 'react';

export default function Header() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [hasScrolled, setHasScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => {
      setHasScrolled(window.scrollY > 10);
    };

    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const scrollToSection = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
      setIsMenuOpen(false);
    }
  };

  return (
    <header
      className={`fixed top-0 left-0 right-0 bg-black border-b border-gray-800 z-50 transition-shadow ${
        hasScrolled ? 'shadow-lg' : ''
      }`}
    >
      <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div className="flex items-center gap-2 cursor-pointer" onClick={() => scrollToSection('inicio')}>
          <img
            src={logoMvlBranco}
            alt="Logo MVLopes"
            className="h-8 w-auto max-w-[140px]"
          />
        </div>

        <nav className="hidden md:flex items-center gap-8">
          <button onClick={() => scrollToSection('inicio')} className="text-sm font-medium text-gray-100 hover:text-[#1052E0] transition-colors">
            Início
          </button>
          <button onClick={() => scrollToSection('servicos')} className="text-sm font-medium text-gray-100 hover:text-[#1052E0] transition-colors">
            Serviços
          </button>
          <button onClick={() => scrollToSection('clientes')} className="text-sm font-medium text-gray-100 hover:text-[#1052E0] transition-colors">
            Clientes
          </button>
          <button onClick={() => scrollToSection('projetos')} className="text-sm font-medium text-gray-100 hover:text-[#1052E0] transition-colors">
            Projetos
          </button>
          <button onClick={() => scrollToSection('contato')} className="text-sm font-medium text-gray-100 hover:text-[#1052E0] transition-colors">
            Contato
          </button>
          <button className="px-6 py-2 bg-[#1052E0] text-white text-sm font-medium rounded hover:bg-[#0d42b8] transition-colors">
            Acessar
          </button>
        </nav>

        <button
          className="md:hidden"
          onClick={() => setIsMenuOpen(!isMenuOpen)}
        >
          {isMenuOpen ? <X size={24} /> : <Menu size={24} />}
        </button>
      </div>

      {isMenuOpen && (
        <div className="md:hidden bg-black border-t border-gray-800">
          <nav className="flex flex-col px-6 py-4 gap-4">
            <button onClick={() => scrollToSection('inicio')} className="text-sm font-medium text-gray-100 text-left">
              Início
            </button>
            <button onClick={() => scrollToSection('servicos')} className="text-sm font-medium text-gray-100 text-left">
              Serviços
            </button>
            <button onClick={() => scrollToSection('clientes')} className="text-sm font-medium text-gray-100 text-left">
              Clientes
            </button>
            <button onClick={() => scrollToSection('projetos')} className="text-sm font-medium text-gray-100 text-left">
              Projetos
            </button>
            <button onClick={() => scrollToSection('contato')} className="text-sm font-medium text-gray-100 text-left">
              Contato
            </button>
            <button className="px-6 py-2 bg-[#1052E0] text-white text-sm font-medium rounded hover:bg-[#0d42b8] transition-colors">
              Acessar
            </button>
          </nav>
        </div>
      )}
    </header>
  );
}
