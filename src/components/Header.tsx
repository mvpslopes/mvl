import { Menu, X } from 'lucide-react';
import logoMvlBranco from '../../logo/logo_mvl-2_branco.png';
import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';

export default function Header() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [hasScrolled, setHasScrolled] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    const onScroll = () => {
      setHasScrolled(window.scrollY > 10);
    };

    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const scrollToSection = (id: string) => {
    // Se estiver fora da home, volte pra "/" e só então faça o scroll.
    if (location.pathname !== '/') {
      navigate('/');
      window.setTimeout(() => {
        const element = document.getElementById(id);
        element?.scrollIntoView({ behavior: 'smooth' });
      }, 50);
      return;
    }

    const element = document.getElementById(id);
    element?.scrollIntoView({ behavior: 'smooth' });
    setIsMenuOpen(false);
  };

  return (
    <header
      className={`fixed top-0 left-0 right-0 bg-black/90 backdrop-blur border-b border-white/10 z-50 transition-shadow ${
        hasScrolled ? 'shadow-lg' : ''
      }`}
    >
      <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-brand/70 to-transparent" />
      <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div className="flex items-center gap-2 cursor-pointer" onClick={() => scrollToSection('inicio')}>
          <img
            src={logoMvlBranco}
            alt="Logo MVLopes"
            className="h-8 w-auto max-w-[140px]"
          />
        </div>

        <nav className="hidden md:flex items-center gap-8">
          <button onClick={() => scrollToSection('inicio')} className="text-sm font-semibold text-white/80 hover:text-white transition-colors">
            Início
          </button>
          <button onClick={() => scrollToSection('servicos')} className="text-sm font-semibold text-white/80 hover:text-white transition-colors">
            Serviços
          </button>
          <button onClick={() => scrollToSection('clientes')} className="text-sm font-semibold text-white/80 hover:text-white transition-colors">
            Clientes
          </button>
          <button onClick={() => scrollToSection('projetos')} className="text-sm font-semibold text-white/80 hover:text-white transition-colors">
            Projetos
          </button>
          <button onClick={() => scrollToSection('contato')} className="text-sm font-semibold text-white/80 hover:text-white transition-colors">
            Contato
          </button>
          <Link
            to="/login"
            className="px-6 py-2 bg-brand text-white text-sm font-semibold rounded-md hover:bg-brand/90 transition-colors shadow-sm shadow-black/30"
          >
            Acessar
          </Link>
        </nav>

        <button
          className="md:hidden text-white"
          onClick={() => setIsMenuOpen(!isMenuOpen)}
          aria-label={isMenuOpen ? 'Fechar menu' : 'Abrir menu'}
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
            <Link
              to="/login"
              className="px-6 py-2 bg-brand text-white text-sm font-semibold rounded-md hover:bg-brand/90 transition-colors"
              onClick={() => setIsMenuOpen(false)}
            >
              Acessar
            </Link>
          </nav>
        </div>
      )}
    </header>
  );
}
