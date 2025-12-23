import logoMvlBranco from '../../logo/logo_mvl-2_branco.png';

export default function Footer() {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="py-12 px-6 bg-black text-white">
      <div className="max-w-7xl mx-auto">
        <div className="flex flex-col md:flex-row items-center justify-between gap-6">
          <div className="flex items-center gap-2">
            <img
              src={logoMvlBranco}
              alt="Logo MVLopes branco"
              className="h-5 w-auto max-w-[96px]"
            />
          </div>

          <div className="text-center md:text-right">
            <p className="text-gray-400 text-sm">
              © 2026 Marcus Vinicius Lopes. Todos os direitos reservados.
            </p>
          </div>
        </div>
      </div>
    </footer>
  );
}
